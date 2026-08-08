<?php

namespace App\Http\Controllers;

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

        return view('pc-builder', compact('catalog', 'types', 'rate', 'currencyMode', 'defaultCurrency'));
    }
}
