<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['rate'];

    // Se llama varias veces por request (tienda, producto, carrito,
    // admin) — mismo patrón que Setting::get(), con un TTL corto en
    // vez de "para siempre" porque acá sí puede cambiar durante el día
    // (a diferencia de un setting, que solo cambia cuando alguien lo
    // edita a mano).
    public static function current(): float
    {
        return (float) Cache::remember('exchange_rate.current', now()->addMinutes(10), function () {
            return static::latest()->value('rate') ?? 1;
        });
    }
}
