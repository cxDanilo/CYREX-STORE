<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }
}
