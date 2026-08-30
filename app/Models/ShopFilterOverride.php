<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopFilterOverride extends Model
{
    protected $fillable = ['type_key', 'field_key', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
