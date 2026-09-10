<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedBuildItem extends Model
{
    protected $fillable = [
        'saved_build_id', 'type', 'product_id', 'product_name',
        'product_image_url', 'unit_price_usd', 'qty', 'compat',
    ];

    protected $casts = [
        'compat' => 'array',
    ];

    public function savedBuild()
    {
        return $this->belongsTo(SavedBuild::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): float
    {
        return $this->unit_price_usd * $this->qty;
    }
}
