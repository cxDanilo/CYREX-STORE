<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'parent_id', 'icon', 'icon_image', 'banner_image', 'sort_order', 'component_type',
    ];

    public function getIconImageUrlAttribute(): ?string
    {
        return $this->icon_image ? asset('uploads/'.$this->icon_image) : null;
    }

    public function getBannerImageUrlAttribute(): ?string
    {
        return $this->banner_image ? asset('uploads/'.$this->banner_image) : null;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Categorías ADICIONALES de un producto (ver Product::categories())
    // — un producto puede estar acá sin que esta sea su categoría real.
    public function additionalProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    // Cuenta productos activos por las dos vías (categoría real y
    // adicional) sin traer los productos en sí — para poder ocultar del
    // menú/widgets cualquier categoría o subcategoría vacía sin pagar
    // una consulta aparte por cada una.
    public function scopeWithActiveProductCounts(Builder $query): Builder
    {
        return $query
            ->withCount(['products as active_products_count' => fn ($q) => $q->where('status', 'active')])
            ->withCount(['additionalProducts as active_additional_products_count' => fn ($q) => $q->where('status', 'active')]);
    }

    public function getHasActiveProductsAttribute(): bool
    {
        return ($this->active_products_count ?? 0) + ($this->active_additional_products_count ?? 0) > 0;
    }
}
