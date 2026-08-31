<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

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

        // Mismo patrón de TTL corto que ExchangeRate::current() (10 min):
        // este catálogo se recalculaba entero (8 consultas) en cada visita
        // al armador aunque el catálogo casi no cambie durante el día. Se
        // cachea junto con price_usd (acepta la misma tolerancia de hasta
        // ~10 min de desfase que ya existe en el resto del sitio para el
        // tipo de cambio), así que un producto nuevo o editado puede tardar
        // hasta ese tiempo en reflejarse acá — no hace falta invalidación
        // manual, igual que Setting/ExchangeRate.
        $catalog = Cache::remember('pcbuilder.catalog', now()->addMinutes(10), function () use ($types, $rate) {
            return collect($types)->keys()->mapWithKeys(function (string $type) use ($rate) {
                $products = Product::query()
                    ->where('status', 'active')
                    ->whereHas('category', fn ($q) => $q->where('component_type', $type))
                    ->with('category')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Product $product) => [
                        'id' => $product->id,
                        'name' => $product->name,
                        // Miniatura, no el original: en el armador cada
                        // tarjeta se ve chica pero antes bajaba la foto
                        // completa (hasta 2000px) igual.
                        'image_url' => $product->image_thumb_url,
                        'price_usd' => round($product->priceInUsd($rate), 2),
                        'compat' => $product->compat ?? [],
                    ])
                    ->values();

                return [$type => $products];
            });
        });

        // "Completa tu setup con..." al final del armador — monitores,
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
                ->take(16)
                ->get();
        }

        return view('pc-builder', compact('catalog', 'types', 'rate', 'currencyMode', 'defaultCurrency', 'heroImageUrl', 'peripherals'));
    }
}
