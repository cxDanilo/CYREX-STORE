<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SavedBuild extends Model
{
    protected $fillable = [
        'slug', 'visitor_name', 'status', 'platform', 'ram_qty',
        'wants_assembly', 'assembly_fee_usd', 'total_usd', 'rate', 'approved_at',
    ];

    protected $casts = [
        'wants_assembly' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SavedBuild $build) {
            $build->slug = $build->slug ?: static::generateUniqueSlug();
        });
    }

    // Sin cuentas de visitante, el slug al azar ES la contraseña del
    // armado — quien tiene el link puede verlo aunque todavía esté
    // pending o haya sido rejected. No se usa el id autoincremental
    // en la URL para no dejar adivinar/enumerar armados ajenos.
    public static function generateUniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(10));
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items()
    {
        return $this->hasMany(SavedBuildItem::class);
    }

    public function totalBob(): float
    {
        return $this->total_usd * $this->rate;
    }
}
