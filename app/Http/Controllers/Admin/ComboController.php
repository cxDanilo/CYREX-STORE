<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\ExchangeRate;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ComboController extends Controller
{
    public function index()
    {
        $combos = Combo::withCount('products')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.combos.index', compact('combos'));
    }

    public function create()
    {
        $combo = new Combo(['active' => true, 'currency' => 'USD']);
        $categorizedProducts = $this->categorizedProducts();
        $rate = ExchangeRate::current();

        return view('admin.combos.form', compact('combo', 'categorizedProducts', 'rate'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $productIds = $data['product_ids'];
        unset($data['product_ids']);

        $combo = Combo::create($data);
        $this->syncProducts($combo, $productIds);
        Cache::forget('home.combos');

        return redirect()->route('admin.combos.index')->with('status', 'Combo creado.');
    }

    public function edit(Combo $combo)
    {
        $combo->load('products');
        $categorizedProducts = $this->categorizedProducts();
        $rate = ExchangeRate::current();

        return view('admin.combos.form', compact('combo', 'categorizedProducts', 'rate'));
    }

    public function update(Request $request, Combo $combo)
    {
        $data = $this->validated($request, $combo);
        $productIds = $data['product_ids'];
        unset($data['product_ids']);

        $combo->update($data);
        $this->syncProducts($combo, $productIds);
        Cache::forget('home.combos');

        return redirect()->route('admin.combos.index')->with('status', 'Combo actualizado.');
    }

    public function destroy(Combo $combo)
    {
        $combo->delete();
        Cache::forget('home.combos');

        return back()->with('status', 'Combo eliminado.');
    }

    public function toggleActive(Combo $combo)
    {
        $combo->update(['active' => ! $combo->active]);
        Cache::forget('home.combos');

        return back()->with('status', $combo->active ? 'Combo activado.' : 'Combo desactivado.');
    }

    private function syncProducts(Combo $combo, array $productIds): void
    {
        $combo->products()->sync(
            collect($productIds)->values()->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all()
        );
    }

    private function categorizedProducts()
    {
        return Product::where('status', 'active')
            ->with('category')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Product $product) => $product->category->name ?? 'Sin categoría');
    }

    private function validated(Request $request, ?Combo $combo = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('combos', 'slug')->ignore($combo?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:USD,BOB'],
            'active' => ['nullable', 'boolean'],
            'product_ids' => ['required', 'array', 'min:2'],
            'product_ids.*' => ['exists:products,id'],
        ], [
            'product_ids.required' => 'Elige al menos 2 productos para el combo.',
            'product_ids.min' => 'Elige al menos 2 productos para el combo.',
        ]);

        $data['active'] = $request->boolean('active');

        return $data;
    }
}
