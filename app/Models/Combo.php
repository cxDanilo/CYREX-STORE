<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Combo extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'currency', 'active', 'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('sort_order')
            ->orderBy('combo_product.sort_order');
    }

    public function priceInUsd(float $rate): float
    {
        return $this->currency === 'USD' ? (float) $this->price : (float) $this->price / $rate;
    }

    public function priceInBob(float $rate): float
    {
        return $this->currency === 'BOB' ? (float) $this->price : (float) $this->price * $rate;
    }

    // Suma de lo que costarían los productos incluidos por separado, a su
    // propio precio individual — se usa solo para mostrar el ahorro contra
    // el precio del combo, nunca para cobrar (el carrito siempre cobra el
    // precio del combo como una sola línea, ver App\Support\Cart).
    public function individualTotalUsd(float $rate): float
    {
        return $this->products->sum(fn (Product $product) => $product->priceInUsd($rate));
    }

    // Foto del combo: la del primer producto incluido (por sort_order) —
    // no hace falta pedirle al admin que suba una imagen aparte para algo
    // que ya es un conjunto de fotos existentes. image_url (original) para
    // la página de detalle, image_thumb_url para las tarjetas chicas.
    public function getImageUrlAttribute(): ?string
    {
        return $this->products->first()?->image_url;
    }

    public function getImageThumbUrlAttribute(): ?string
    {
        return $this->products->first()?->image_thumb_url;
    }
}
