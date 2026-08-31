<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'currency',
        'sku', 'has_variants', 'status', 'specs', 'image', 'compat',
        'is_sold_out', 'sold_out_at', 'promotion_id', 'offer_price', 'offer_selected',
    ];

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
        return $this->image ? asset('uploads/'.$this->image) : null;
    }

    /**
     * Miniatura de 400px que ImageOptimizer::process() ya genera al subir
     * la imagen (mismo directorio, prefijo "thumb_") — para listados donde
     * la foto se muestra chica (armador, tienda, relacionados) esto evita
     * bajar el original de hasta 2000px. Cae al original si la miniatura
     * no existe (fotos subidas antes de que existiera este optimizador).
     */
    public function getImageThumbUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        $dir = dirname($this->image);
        $thumbPath = ($dir === '.' ? '' : $dir.'/').'thumb_'.basename($this->image);

        return Storage::disk('uploads')->exists($thumbPath)
            ? asset('uploads/'.$thumbPath)
            : $this->image_url;
    }

    public function getComponentTypeAttribute(): ?string
    {
        return $this->category?->component_type;
    }

    /**
     * Ofertas: lote único con switch e ídem fecha global (Admin → Ofertas),
     * a propósito separado del sistema de Promotion (ese es cosmético —
     * banners/badges estacionales — y nunca tocó precios). offer_selected
     * y offer_price van separados adrede: desmarcar un producto de la
     * tanda actual no borra su precio de oferta, así la próxima vez que
     * se arme una oferta parecida no hay que volver a escribir los precios
     * de cero (ver Admin\OfferController).
     */
    public function hasActiveOffer(): bool
    {
        return $this->offer_selected
            && $this->offer_price !== null
            && (float) $this->offer_price < (float) $this->price
            && Setting::get('offer_active', '0') === '1'
            && $this->offerEndsAt()?->isFuture();
    }

    public function offerEndsAt(): ?Carbon
    {
        $raw = Setting::get('offer_ends_at');

        return $raw ? Carbon::parse($raw) : null;
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

    public function offerDiscountPercent(): int
    {
        if (! $this->hasActiveOffer()) {
            return 0;
        }

        return (int) round((1 - ((float) $this->offer_price / (float) $this->price)) * 100);
    }
}
