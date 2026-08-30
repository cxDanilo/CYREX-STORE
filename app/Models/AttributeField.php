<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttributeField extends Model
{
    protected $fillable = [
        'type_key',
        'type_label',
        'field_key',
        'label',
        'field_type',
        'options',
        'shop_filter',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'shop_filter' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('type_key')->orderBy('sort_order')->orderBy('id');
    }
}
