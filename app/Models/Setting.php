<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    /**
     * El logo subido en Ajustes → Marca, o el default del repo si nadie
     * cargó uno todavía. Un solo lugar para esta lógica — la usan tanto
     * el sitio público (AppServiceProvider) como el login de admin.
     */
    public static function logoUrl(): string
    {
        $path = static::get('logo_path');

        return $path ? Storage::disk('uploads')->url($path) : asset('images/logo-horizontal.png');
    }
}
