<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountGroup;
use App\Models\Product;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DiscountGroupController extends Controller
{
    public function index()
    {
        $group = DiscountGroup::with('products')->first();
        $categorizedProducts = $this->categorizedProducts();

        return view('admin.discount-groups.edit', compact('group', 'categorizedProducts'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        if ($error = $this->priceError($data)) {
            return back()->withErrors($error)->withInput();
        }

        $group = DiscountGroup::create([
            'name' => $data['name'],
            'starts_at' => $this->parseDateTime($data['starts_at']),
            'ends_at' => $this->parseDateTime($data['ends_at']),
        ]);

        $this->applySelection($group, $data);

        return redirect()->route('admin.descuentos.index')->with('status', 'Campaña creada — ahora agregale productos.');
    }

    public function update(Request $request, DiscountGroup $discountGroup)
    {
        $data = $this->validateRequest($request);

        if ($error = $this->priceError($data)) {
            return back()->withErrors($error)->withInput();
        }

        $discountGroup->update([
            'name' => $data['name'],
            'starts_at' => $this->parseDateTime($data['starts_at']),
            'ends_at' => $this->parseDateTime($data['ends_at']),
        ]);

        // Los que ya no vienen tildados salen del grupo — se les respeta
        // el offer_price guardado (por si se los vuelve a sumar después)
        // pero dejan de estar "en oferta" y de contar para esta campaña.
        $selectedIds = array_map('intval', $data['product_ids'] ?? []);
        $discountGroup->products()->whereNotIn('id', $selectedIds)->update([
            'offer_selected' => false,
            'discount_group_id' => null,
        ]);

        $this->applySelection($discountGroup, $data);

        return redirect()->route('admin.descuentos.index')->with('status', 'Campaña actualizada.');
    }

    // "Terminar campaña ahora" — misma limpieza que corre sola el
    // comando programado discount-groups:expire cuando se vence.
    public function destroy(DiscountGroup $discountGroup)
    {
        $count = $discountGroup->revertProducts()->count();

        return redirect()->route('admin.descuentos.index')->with('status', "Campaña terminada — {$count} producto(s) vuelto(s) a su precio normal.");
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'product_ids' => ['array'],
            'product_ids.*' => ['exists:products,id'],
            'offer_price' => ['array'],
            'offer_price.*' => ['nullable', 'numeric', 'min:0.01'],
        ], [
            'ends_at.after' => 'La fecha de fin tiene que ser posterior a la de inicio.',
        ]);
    }

    // Mismo chequeo que ya hacía Admin\OfferController: cada producto
    // tildado necesita un precio de oferta, y tiene que ser menor al
    // precio real — devuelve el mensaje listo para withErrors(), o
    // null si todo está bien.
    private function priceError(array $data): ?array
    {
        $selectedIds = array_map('intval', $data['product_ids'] ?? []);
        $prices = $data['offer_price'] ?? [];
        $products = Product::whereIn('id', $selectedIds)->get(['id', 'name', 'price'])->keyBy('id');

        foreach ($selectedIds as $id) {
            $product = $products->get($id);
            $reduced = isset($prices[$id]) ? (float) $prices[$id] : null;

            if ($reduced === null) {
                return ["offer_price.{$id}" => "Falta el precio de oferta de \"{$product->name}\"."];
            }

            if ($reduced >= (float) $product->price) {
                return ["offer_price.{$id}" => "El precio de oferta de \"{$product->name}\" debe ser menor a su precio real (\${$product->price})."];
            }
        }

        return null;
    }

    private function applySelection(DiscountGroup $group, array $data): void
    {
        $selectedIds = array_map('intval', $data['product_ids'] ?? []);
        $prices = $data['offer_price'] ?? [];

        Setting::set('offer_active', '1');
        Setting::set('offer_starts_at', $group->starts_at->toIso8601String());
        Setting::set('offer_ends_at', $group->ends_at->toIso8601String());

        foreach ($selectedIds as $id) {
            Product::where('id', $id)->update([
                'offer_price' => $prices[$id],
                'offer_selected' => true,
                'discount_group_id' => $group->id,
            ]);
        }
    }

    // La hora que escribe el admin es hora de Bolivia — guardarla tal
    // cual (sin decirle a Carbon en qué zona horaria está) la dejaría
    // corrida contra UTC, que es lo que usa el resto de la app.
    private function parseDateTime(string $value): Carbon
    {
        return Carbon::parse($value, 'America/La_Paz')->utc();
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
