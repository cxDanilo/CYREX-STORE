<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'variant_type', 'variant_value', 'sku', 'stock', 'price_override',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
