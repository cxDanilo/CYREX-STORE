<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Setting;

class PcBuilderController extends Controller
{
    public function index()
    {
        $rate = ExchangeRate::current();
        $currencyMode = Setting::get('currency_mode', 'both');
        $defaultCurrency = Setting::get('default_currency', 'USD');
        $heroImage = Setting::get('pcbuilder_hero_image');
        $heroImageUrl = $heroImage ? asset('uploads/'.$heroImage) : null;

        $types = config('pc_builder.component_types');

        $catalog = collect($types)->keys()->mapWithKeys(function (string $type) use ($rate) {
            $products = Product::query()
                ->where('status', 'active')
                ->whereHas('category', fn ($q) => $q->where('component_type', $type))
                ->with('category')
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->image_url,
                    'price_usd' => round($product->priceInUsd($rate), 2),
                    'compat' => $product->compat ?? [],
                ])
                ->values();

            return [$type => $products];
        });

        // "Completá tu setup con..." al final del armador — monitores,
        // teclados, mouse, etc. viven todos bajo la categoría padre
        // Periféricos, así que alcanza con traer productos de ese árbol
        // sin filtrar por component_type (a diferencia del catálogo de
        // arriba, que sí es pieza por pieza).
        $peripherals = collect();
        $peripheralsCategory = Category::where('slug', 'perifericos')->first();
        if ($peripheralsCategory) {
            $categoryIds = $peripheralsCategory->children()->pluck('id')->push($peripheralsCategory->id);
            $peripherals = Product::where('status', 'active')
                ->whereIn('category_id', $categoryIds)
                ->with('category')
                ->inRandomOrder()
                ->take(8)
                ->get();
        }

        return view('pc-builder', compact('catalog', 'types', 'rate', 'currencyMode', 'defaultCurrency', 'heroImageUrl', 'peripherals'));
    }
}
