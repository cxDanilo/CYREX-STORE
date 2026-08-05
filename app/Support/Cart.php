<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    private const SESSION_KEY = 'cart';

    public static function add(int $productId, ?int $variantId = null): void
    {
        $items = self::raw();
        $items[self::key($productId, $variantId)] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
        ];
        Session::put(self::SESSION_KEY, $items);
    }

    public static function remove(string $key): void
    {
        $items = self::raw();
        unset($items[$key]);
        Session::put(self::SESSION_KEY, $items);
    }

    public static function count(): int
    {
        return count(self::raw());
    }

    public static function items(): Collection
    {
        $raw = self::raw();

        if (empty($raw)) {
            return collect();
        }

        $products = Product::with('category')
            ->whereIn('id', collect($raw)->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $variantIds = collect($raw)->pluck('variant_id')->filter()->unique();
        $variants = $variantIds->isNotEmpty()
            ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        return collect($raw)->map(function ($entry, $key) use ($products, $variants) {
            $product = $products->get($entry['product_id']);

            if (! $product) {
                return null;
            }

            $variant = $entry['variant_id'] ? $variants->get($entry['variant_id']) : null;

            return (object) [
                'key' => $key,
                'product' => $product,
                'variant' => $variant,
                'price' => (float) ($variant?->price_override ?? $product->price),
                'currency' => $product->currency,
            ];
        })->filter()->values();
    }

    public static function total(float $rate, string $currency = 'USD'): float
    {
        return self::items()->sum(function ($item) use ($rate, $currency) {
            $priceUsd = $item->currency === 'USD' ? $item->price : $item->price / $rate;

            return $currency === 'USD' ? $priceUsd : $priceUsd * $rate;
        });
    }

    public static function whatsappMessage(string $number, float $rate, string $currency = 'USD'): string
    {
        $items = self::items();

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['Hola! Quiero pedir:'];

        foreach ($items as $item) {
            $name = $item->product->name;

            if ($item->variant) {
                $name .= ' ('.$item->variant->variant_value.')';
            }

            $priceLabel = $item->currency === 'USD'
                ? '$'.number_format($item->price, 2)
                : 'Bs '.number_format($item->price, 2);

            $lines[] = "- {$name} — {$priceLabel}";
        }

        $totalLabel = $currency === 'USD'
            ? '$'.number_format(self::total($rate, $currency), 2)
            : 'Bs '.number_format(self::total($rate, $currency), 2);

        $lines[] = '';
        $lines[] = "Total aproximado: {$totalLabel}";

        return 'https://wa.me/'.$number.'?text='.urlencode(implode("\n", $lines));
    }

    private static function key(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?? '');
    }

    private static function raw(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }
}
