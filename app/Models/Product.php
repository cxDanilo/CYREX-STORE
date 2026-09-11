<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'currency',
        'sku', 'has_variants', 'status', 'specs', 'image', 'compat',
        'is_sold_out', 'sold_out_at', 'promotion_id', 'offer_price', 'offer_selected',
        'discount_group_id',
    ];

    // Se sanitiza al GUARDAR (no en cada lectura) — se imprime sin escapar
    // en la ficha pública ({!! $product->description !!}) y también la
    // lee directo el x-html de Alpine, así que sanitizar solo en el Blade
    // no alcanzaba: había que limpiarla en el origen, para que CUALQUIER
    // lugar que la lea (la ficha, el JSON de edición rápida, el propio
    // form de edición) ya reciba el valor limpio. Cubre tanto el form de
    // admin como el importador de WooCommerce (ambos hacen
    // $product->description = ..., no un insert crudo). Ver auditoría de
    // seguridad, hallazgo F2 — a diferencia del bloque CMS "html_libre",
    // que es intencionalmente HTML crudo sin sanitizar (ver
    // config/cms_blocks.php) y ahora solo lo puede tocar un admin (F1).
    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = HtmlSanitizer::description($value);
    }

    protected $casts = [
        'specs' => 'array',
        'compat' => 'array',
        'has_variants' => 'boolean',
        'is_sold_out' => 'boolean',
        'sold_out_at' => 'datetime',
        'price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'offer_selected' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Campaña de descuento que puso el offer_price/offer_selected
    // actuales, si vinieron de una (ver Admin\DiscountGroupController)
    // — null si el producto se puso en oferta a mano sin campaña.
    public function discountGroup(): BelongsTo
    {
        return $this->belongsTo(DiscountGroup::class);
    }

    // Categorías ADICIONALES — category() de arriba sigue siendo LA
    // categoría real del producto (filtro de atributos, migas de pan,
    // etc.). Esto es para que también aparezca listado en otros lados
    // (ej. una categoría "Promociones" armada a mano) sin perderla.
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Un producto está en promo si se le asignó una directamente, o si su
     * categoría entera está marcada en la promo activa ahora mismo.
     */
    public function activePromotion(?Promotion $activePromotion): ?Promotion
    {
        if (! $activePromotion) {
            return null;
        }

        if ($this->promotion_id === $activePromotion->id) {
            return $activePromotion;
        }

        if ($activePromotion->category_id && $activePromotion->category_id === $this->category_id) {
            return $activePromotion;
        }

        return null;
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * La imagen "de portada" (la de siempre, $product->image) primero, y
     * atrás las adicionales de la galería — este orden es el que define
     * cuál se ve primero en la página de producto y en cualquier lugar
     * que solo muestre una miniatura.
     */
    public function getGalleryUrlsAttribute(): Collection
    {
        return collect([$this->image_url])
            ->filter()
            ->merge($this->images->pluck('url'));
    }

    public function priceInUsd(float $rate): float
    {
        return $this->currency === 'USD' ? (float) $this->price : (float) $this->price / $rate;
    }

    public function priceInBob(float $rate): float
    {
        return $this->currency === 'BOB' ? (float) $this->price : (float) $this->price * $rate;
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('uploads/'.$this->image);
        }

        // Sin foto propia: productos que se manejan solo con foto por
        // variante (ej. un color por variante, sin una "genérica" de
        // por medio, como Kumara en negro/blanco) no deberían verse en
        // blanco en toda la tienda, el carrito, o la vista previa al
        // compartir el link — se usa la de la primera variante que sí
        // tenga una en vez de dejarlo vacío.
        return $this->variants->first(fn ($v) => $v->image)?->image_url;
    }

    /**
     * Miniatura de 400px que ImageOptimizer::process() ya genera al subir
     * la imagen (mismo directorio, prefijo "thumb_") — para listados donde
     * la foto se muestra chica (armador, tienda, relacionados) esto evita
     * bajar el original de hasta 2000px. Cae al original si la miniatura
     * no existe (fotos subidas antes de que existiera este optimizador),
     * o si la foto en sí es la de respaldo de una variante (ver
     * getImageUrlAttribute() — esas no tienen su propia miniatura).
     */
    public function getImageThumbUrlAttribute(): ?string
    {
        if (! $this->image) {
            return $this->image_url;
        }

        // La miniatura siempre se guarda en .webp (ver ImageOptimizer),
        // sea cual sea la extensión del original.
        $dir = dirname($this->image);
        $nameWithoutExt = pathinfo($this->image, PATHINFO_FILENAME);
        $thumbPath = ($dir === '.' ? '' : $dir.'/').'thumb_'.$nameWithoutExt.'.webp';

        return Storage::disk('uploads')->exists($thumbPath)
            ? asset('uploads/'.$thumbPath)
            : $this->image_url;
    }

    public function getComponentTypeAttribute(): ?string
    {
        return $this->category?->component_type;
    }

    /**
     * Ofertas: lote único con switch e ídem fecha global (Admin →
     * Descuentos, ver Admin\DiscountGroupController y DiscountGroup),
     * a propósito separado del sistema de Promotion (ese es cosmético —
     * banners/badges estacionales — y nunca tocó precios). offer_selected
     * y offer_price van separados adrede: sacar un producto de una
     * campaña (sin que la campaña entera termine) no borra su precio de
     * oferta, así la próxima vez que se arme una oferta parecida no hay
     * que volver a escribir los precios de cero.
     */
    public function hasActiveOffer(): bool
    {
        return $this->offer_selected
            && $this->offer_price !== null
            && (float) $this->offer_price < (float) $this->price
            && Setting::get('offer_active', '0') === '1'
            && ($this->offerStartsAt()?->isPast() ?? true)
            && $this->offerEndsAt()?->isFuture();
    }

    public function offerStartsAt(): ?Carbon
    {
        $raw = Setting::get('offer_starts_at');

        return $raw ? Carbon::parse($raw) : null;
    }

    public function offerEndsAt(): ?Carbon
    {
        $raw = Setting::get('offer_ends_at');

        return $raw ? Carbon::parse($raw) : null;
    }

    // Mismas condiciones que hasActiveOffer() de arriba, pero como
    // filtro de consulta — para la categoría automática de ofertas
    // (Ajustes), que necesita encontrar los productos en oferta sin
    // cargar todo el catálogo a PHP primero. offer_active/offer_starts_at/
    // offer_ends_at son globales (un solo Setting, no una columna por
    // producto), así que esa parte se resuelve una sola vez acá, no por
    // fila.
    public function scopeHasActiveOffer(Builder $query): Builder
    {
        if (Setting::get('offer_active', '0') !== '1') {
            return $query->whereRaw('1 = 0');
        }

        $startsAt = Setting::get('offer_starts_at');
        if ($startsAt && ! Carbon::parse($startsAt)->isPast()) {
            return $query->whereRaw('1 = 0');
        }

        $endsAt = Setting::get('offer_ends_at');
        if (! $endsAt || ! Carbon::parse($endsAt)->isFuture()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('offer_selected', true)
            ->whereNotNull('offer_price')
            ->whereColumn('offer_price', '<', 'price');
    }

    // Precio real si no hay oferta activa, precio de oferta si la hay.
    // A propósito NO se usa dentro de priceInUsd()/priceInBob() — esos dos
    // alimentan Combos y Arma tu PC, que quedan aislados de las ofertas
    // (ver el plan: acoplar ambos sistemas tendría efectos raros, como que
    // el "ahorrás $X" de un combo cambie solo porque una pieza suya entró
    // en oferta en la tienda, sin que el admin tocara el combo).
    public function effectivePrice(): float
    {
        return $this->hasActiveOffer() ? (float) $this->offer_price : (float) $this->price;
    }

    // A diferencia de priceInBob() (que a propósito ignora ofertas, ver
    // arriba), esta sí respeta una oferta activa — la usa el toggle de
    // "solo Bs" del admin (AdminCurrencyPref) para la tienda/ficha, que
    // deben verse igual que para el público salvo por la moneda.
    public function effectivePriceInBob(float $rate): float
    {
        return $this->currency === 'BOB' ? $this->effectivePrice() : $this->effectivePrice() * $rate;
    }

    public function offerDiscountPercent(): int
    {
        if (! $this->hasActiveOffer()) {
            return 0;
        }

        return (int) round((1 - ((float) $this->offer_price / (float) $this->price)) * 100);
    }

    /**
     * true si el producto tiene variantes con AL MENOS dos precios
     * efectivos distintos entre sí (una variante sin precio propio
     * cuenta con el precio del producto, igual que hace la ficha
     * pública) — ahí es cuando tiene sentido mostrar "Desde $X" en vez
     * del precio a secas, porque ese único número no representa lo que
     * termina costando cada variante.
     */
    public function hasVariantPriceRange(): bool
    {
        if (! $this->has_variants || $this->variants->isEmpty()) {
            return false;
        }

        return $this->variants
            ->map(fn (ProductVariant $v) => (float) ($v->price_override ?? $this->effectivePrice()))
            ->unique()
            ->count() > 1;
    }

    /**
     * Precio para tarjetas/listados (tienda, relacionados, buscador):
     * con rango de precios entre variantes, el más barato — es el que
     * de verdad se puede pagar por este producto, ninguna variante
     * cuesta menos. Sin rango (sin variantes, o todas al mismo precio),
     * el de siempre. Ver hasVariantPriceRange() para cuándo antepone
     * "Desde" en la vista.
     */
    public function displayPrice(): float
    {
        if (! $this->hasVariantPriceRange()) {
            return $this->effectivePrice();
        }

        return $this->variants
            ->map(fn (ProductVariant $v) => (float) ($v->price_override ?? $this->effectivePrice()))
            ->min();
    }

    public function displayPriceInBob(float $rate): float
    {
        return $this->currency === 'BOB' ? $this->displayPrice() : $this->displayPrice() * $rate;
    }
}
