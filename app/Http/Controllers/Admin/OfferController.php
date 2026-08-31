<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function edit()
    {
        $active = Setting::get('offer_active', '0') === '1';
        $endsAt = Setting::get('offer_ends_at');
        $categorizedProducts = $this->categorizedProducts();

        return view('admin.offers.edit', compact('active', 'endsAt', 'categorizedProducts'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'active' => ['nullable', 'boolean'],
            'ends_at' => ['required', 'date'],
            'product_ids' => ['array'],
            'product_ids.*' => ['exists:products,id'],
            'offer_price' => ['array'],
            'offer_price.*' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $selectedIds = array_map('intval', $data['product_ids'] ?? []);
        $prices = $data['offer_price'] ?? [];

        // El precio real se necesita para validar "menor al real" — se
        // trae de una sola vez para no consultar producto por producto.
        $products = Product::whereIn('id', $selectedIds)->get(['id', 'name', 'price'])->keyBy('id');

        foreach ($selectedIds as $id) {
            $product = $products->get($id);
            $reduced = isset($prices[$id]) ? (float) $prices[$id] : null;

            if ($reduced === null) {
                return back()->withErrors(["offer_price.{$id}" => "Falta el precio de oferta de \"{$product->name}\"."])->withInput();
            }

            if ($reduced >= (float) $product->price) {
                return back()->withErrors(["offer_price.{$id}" => "El precio de oferta de \"{$product->name}\" debe ser menor a su precio real (\${$product->price})."])->withInput();
            }
        }

        Setting::set('offer_active', $request->boolean('active') ? '1' : '0');
        // La hora que escribe el admin es hora de Bolivia — guardarla tal
        // cual (sin decirle a Carbon en qué zona horaria está) la dejaría
        // corrida contra UTC, que es lo que usa el resto de la app.
        Setting::set('offer_ends_at', Carbon::parse($data['ends_at'], 'America/La_Paz')->utc()->toIso8601String());

        // offer_selected marca la tanda ACTUAL; offer_price de los que se
        // desmarcan queda tal cual (no se borra) para la próxima oferta.
        Product::whereIn('id', $selectedIds)->update(['offer_selected' => true]);
        Product::whereNotIn('id', $selectedIds)->where('offer_selected', true)->update(['offer_selected' => false]);

        foreach ($selectedIds as $id) {
            Product::where('id', $id)->update(['offer_price' => $prices[$id]]);
        }

        return redirect()->route('admin.ofertas.edit')->with('status', 'Oferta guardada.');
    }

    private function categorizedProducts()
    {
        return Product::where('status', 'active')
            ->with(['category', 'variants'])
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Product $product) => $product->category->name ?? 'Sin categoría');
    }
}
