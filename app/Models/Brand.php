<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'logo', 'accent_color', 'is_page_published', 'page_data'];

    protected $casts = [
        'is_page_published' => 'boolean',
        'page_data' => 'array',
    ];

    /**
     * /marcas/{brand:slug} en vez de /marcas/{brand:id} -- la URL de una
     * marca tiene que ser legible (/marcas/ajazz), no un ID.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('uploads/'.$this->logo) : null;
    }

    /**
     * products.brand sigue siendo texto libre (ver la migración que
     * agregó esa columna) -- no hay foreign key a esta tabla, así que
     * "los productos de esta marca" siempre se resuelven comparando el
     * nombre, igual que ShopController::index() para el filtro ?marca=.
     */
    public function products()
    {
        return Product::where('status', 'active')
            ->whereRaw('LOWER(brand) = ?', [mb_strtolower($this->name)]);
    }

    /**
     * Trae varios productos de esta marca por ID, en el mismo orden en
     * que se pasaron los IDs (no el orden natural de la tabla) -- las
     * secciones de la página de marca (hero, tarjetas, comparador)
     * guardan listas ordenadas de IDs en page_data y necesitan
     * respetar ese orden tal cual lo dejó el admin.
     */
    public function productsByIds(?array $ids): \Illuminate\Support\Collection
    {
        $ids = array_filter((array) $ids);
        if (empty($ids)) {
            return collect();
        }

        $products = Product::whereIn('id', $ids)->where('status', 'active')->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $products->get($id))->filter()->values();
    }
}
