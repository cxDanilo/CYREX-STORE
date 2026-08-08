<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'currency',
        'sku', 'stock', 'has_variants', 'status', 'specs', 'image', 'compat',
    ];

    protected $casts = [
        'specs' => 'array',
        'compat' => 'array',
        'has_variants' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
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
