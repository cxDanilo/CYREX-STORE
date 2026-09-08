<?php

namespace App\Support;

use App\Models\DiscountGroup;
use App\Models\Product;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Poner/sacar UN producto de la oferta activa — misma lógica usada
 * desde dos lugares (Admin\ProductController::quickEdit y
 * ProductQuickEditController, el editor rápido de la propia página
 * pública del producto), antes duplicada en el primero. Si ya hay una
 * campaña corriendo (Admin → Descuentos), el producto se suma a esa
 * misma fecha; si no hay ninguna, arma una campaña de un solo
 * producto con la fecha que se indique, para que quede cubierta igual
 * por el vencimiento automático (discount-groups:expire).
 */
class ProductOfferManager
{
    public static function apply(Product $product, bool $enabling, mixed $offerPrice, mixed $endsAt): void
    {
        $previousGroupId = $product->discount_group_id;

        if ($enabling) {
            self::enable($product, $offerPrice, $endsAt);
        } else {
            $product->update(['offer_selected' => false, 'discount_group_id' => null]);
        }

        self::deleteIfNowEmpty($previousGroupId);
    }

    private static function enable(Product $product, mixed $offerPrice, mixed $endsAt): void
    {
        $price = (float) $offerPrice;

        if ($price >= (float) $product->price) {
            throw ValidationException::withMessages([
                'offer_price' => "El precio de oferta debe ser menor al precio real (\${$product->price}).",
            ]);
        }

        $activeGroup = DiscountGroup::first();

        if (! $activeGroup && ! $endsAt) {
            throw ValidationException::withMessages([
                'ends_at' => 'Poné una fecha de fin para esta oferta.',
            ]);
        }

        if (! $activeGroup) {
            $activeGroup = DiscountGroup::create([
                'name' => "Oferta rápida: {$product->name}",
                'ends_at' => Carbon::parse($endsAt, 'America/La_Paz')->utc(),
            ]);
            Setting::set('offer_active', '1');
            Setting::set('offer_ends_at', $activeGroup->ends_at->toIso8601String());
        }

        $product->update([
            'offer_price' => $offerPrice,
            'offer_selected' => true,
            'discount_group_id' => $activeGroup->id,
        ]);
    }

    // Si esta era la única oferta de la campaña y se acaba de sacar, no
    // tiene sentido dejar una campaña vacía esperando su fecha de fin
    // sola.
    private static function deleteIfNowEmpty(?int $groupId): void
    {
        if (! $groupId) {
            return;
        }

        $group = DiscountGroup::find($groupId);

        if ($group && $group->products()->doesntExist()) {
            $group->delete();
            Setting::set('offer_active', '0');
        }
    }
}
