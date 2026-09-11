<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

// Una campaña de descuento con nombre y fecha de fin propia, aplicada
// a varios productos a la vez (ver Admin\DiscountGroupController). En
// la práctica esta tabla tiene 0 o 1 filas: el comando programado
// discount-groups:expire borra la fila apenas se vence, después de
// devolver sus productos al precio normal.
class DiscountGroup extends Model
{
    protected $fillable = ['name', 'starts_at', 'ends_at'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Usado tanto al "Terminar campaña ahora" desde el admin como por
    // el comando programado discount-groups:expire — un solo lugar
    // para no duplicar la lógica de "devolver todo a la normalidad".
    // Vuelve productos a su precio real de verdad (no los deja
    // "recordados" para la próxima, a diferencia de como funcionaba
    // antes Admin\OfferController) y apaga el contador público.
    public function revertProducts(): Collection
    {
        $products = $this->products()->get();

        foreach ($products as $product) {
            $before = $product->getAttributes();
            $product->update([
                'offer_price' => null,
                'offer_selected' => false,
                'discount_group_id' => null,
            ]);
            ProductActivityLog::logFieldChanges($product, $before);
        }

        Setting::set('offer_active', '0');
        $this->delete();

        return $products;
    }
}
