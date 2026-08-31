<?php

namespace App\Support;

use App\Models\Combo;
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

    // Un combo se guarda como UNA sola línea con su propio precio de
    // combo — nunca como los productos que incluye a su precio individual
    // (eso perdería el sentido de la oferta). "combo:{id}" nunca puede
    // chocar con una clave de producto (esas son siempre "{id}:{id|''}").
    public static function addCombo(int $comboId): void
    {
        $items = self::raw();
        $items[self::comboKey($comboId)] = [
            'combo_id' => $comboId,
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

        $productEntries = collect($raw)->filter(fn ($e) => isset($e['product_id']));
        $comboEntries = collect($raw)->filter(fn ($e) => isset($e['combo_id']));

        $products = Product::with('category')
            ->whereIn('id', $productEntries->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $variantIds = $productEntries->pluck('variant_id')->filter()->unique();
        $variants = $variantIds->isNotEmpty()
            ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $combos = $comboEntries->isNotEmpty()
            ? Combo::with('products')->whereIn('id', $comboEntries->pluck('combo_id')->unique())->get()->keyBy('id')
            : collect();

        return collect($raw)->map(function ($entry, $key) use ($products, $variants, $combos) {
            if (isset($entry['combo_id'])) {
                $combo = $combos->get($entry['combo_id']);

                if (! $combo) {
                    return null;
                }

                return (object) [
                    'key' => $key,
                    'type' => 'combo',
                    'product' => null,
                    'variant' => null,
                    'combo' => $combo,
                    'price' => (float) $combo->price,
                    'currency' => $combo->currency,
                ];
            }

            $product = $products->get($entry['product_id']);

            if (! $product) {
                return null;
            }

            $variant = $entry['variant_id'] ? $variants->get($entry['variant_id']) : null;

            return (object) [
                'key' => $key,
                'type' => 'product',
                'product' => $product,
                'variant' => $variant,
                'combo' => null,
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
            $priceLabel = $item->currency === 'USD'
                ? '$'.number_format($item->price, 2)
                : 'Bs '.number_format($item->price, 2);

            if ($item->type === 'combo') {
                $included = $item->combo->products->pluck('name')->implode(', ');
                $lines[] = "- Combo: {$item->combo->name} (incluye: {$included}) — {$priceLabel}";

                continue;
            }

            $name = $item->product->name;

            if ($item->variant) {
                $name .= ' ('.$item->variant->variant_value.')';
            }

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

    private static function comboKey(int $comboId): string
    {
        return 'combo:'.$comboId;
    }

    private static function raw(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }
}
