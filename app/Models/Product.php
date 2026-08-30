<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'currency',
        'sku', 'has_variants', 'status', 'specs', 'image', 'compat',
        'is_sold_out', 'sold_out_at', 'promotion_id',
    ];

    protected $casts = [
        'specs' => 'array',
        'compat' => 'array',
        'has_variants' => 'boolean',
        'is_sold_out' => 'boolean',
        'sold_out_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Un producto está en promo si se le asignó una directamente, o si su
     * categoría entera está marcada en la promo activa ahora mismo.
     */
    public function activePromotion(?Promotion $activePromotion): ?Promotion
    {
        if (! $activePromotion) {
            return null;
        }

        if ($this->promotion_id === $activePromotion->id) {
            return $activePromotion;
        }

        if ($activePromotion->category_id && $activePromotion->category_id === $this->category_id) {
            return $activePromotion;
        }

        return null;
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * La imagen "de portada" (la de siempre, $product->image) primero, y
     * atrás las adicionales de la galería — este orden es el que define
     * cuál se ve primero en la página de producto y en cualquier lugar
     * que solo muestre una miniatura.
     */
    public function getGalleryUrlsAttribute(): Collection
    {
        return collect([$this->image_url])
            ->filter()
            ->merge($this->images->pluck('url'));
    }

    public function priceInUsd(float $rate): float
    {
        return $this->currency === 'USD' ? (float) $this->price : (float) $this->price / $rate;
    }

    public function priceInBob(float $rate): float
    {
        return $this->currency === 'BOB' ? (float) $this->price : (float) $this->price * $rate;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('uploads/'.$this->image) : null;
    }

    public function getComponentTypeAttribute(): ?string
    {
        return $this->category?->component_type;
    }
}
