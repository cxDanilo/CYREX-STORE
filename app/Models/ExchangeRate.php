<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['rate'];

    public static function current(): float
    {
        return (float) (static::latest()->value('rate') ?? 1);
    }
}
