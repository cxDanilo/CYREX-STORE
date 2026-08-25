<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'name', 'slug', 'banner_text', 'teaser_text', 'teaser_starts_at',
        'starts_at', 'ends_at', 'discount_label', 'is_recurring',
        'recurring_month', 'recurring_day', 'show_as_modal', 'category_id', 'active',
    ];

    protected $casts = [
        'teaser_starts_at' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_recurring' => 'boolean',
        'show_as_modal' => 'boolean',
        'active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * La promo activa ahora mismo (banner dorado), o null si ninguna.
     */
    public static function active(): ?self
    {
        return static::where('active', true)->get()->first(fn (self $p) => $p->isActiveNow());
    }

    /**
     * La promo en fase de expectativa ahora mismo (antes de arrancar).
     * Puede coexistir con una promo distinta ya activa.
     */
    public static function inTeaser(): ?self
    {
        return static::where('active', true)->get()->first(fn (self $p) => $p->isInTeaserNow());
    }

    /**
     * Lo que corresponde mostrar "ahora" si solo se puede elegir una: la
     * activa tiene prioridad sobre la de expectativa.
     */
    public static function current(): ?self
    {
        return static::active() ?? static::inTeaser();
    }

    public function isActiveNow(): bool
    {
        $today = Carbon::today();

        foreach ($this->windowCandidates() as [$start, $end]) {
            if ($today->between($start, $end)) {
                return true;
            }
        }

        return false;
    }

    public function isInTeaserNow(): bool
    {
        if (! $this->teaser_text || ! $this->teaser_starts_at) {
            return false;
        }

        $today = Carbon::today();

        foreach ($this->windowCandidates() as [$start, , $teaserStart]) {
            if ($teaserStart && $today->between($teaserStart, $start->copy()->subDay()->endOfDay())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hasta cuándo corre la ventana activa vigente — lo que necesita el
     * countdown de la barra de anuncio. Null si no está activa ahora.
     */
    public function currentEndsAt(): ?Carbon
    {
        $today = Carbon::today();

        foreach ($this->windowCandidates() as [$start, $end]) {
            if ($today->between($start, $end)) {
                return $end;
            }
        }

        return null;
    }

    /**
     * Para promos NO recurrentes, la ventana es literal (starts_at/ends_at
     * tal cual guardados). Para recurrentes, recurring_month/recurring_day
     * marcan el DÍA DEL EVENTO (ej. 25 de diciembre) — starts_at/ends_at
     * originales solo definen cuántos días dura la promo y cuántos días
     * antes arranca el teaser, y esa duración se reproyecta sobre el año
     * actual. Se evalúan año actual ± 1 para no perder promos de fin/
     * inicio de año (ej. Año Nuevo) al calcular cerca del cambio de año.
     *
     * @return array<int, array{0: Carbon, 1: Carbon, 2: ?Carbon}>
     */
    private function windowCandidates(): array
    {
        if (! $this->is_recurring || ! $this->recurring_month || ! $this->recurring_day) {
            return [[
                $this->starts_at->copy()->startOfDay(),
                $this->ends_at->copy()->endOfDay(),
                $this->teaser_starts_at?->copy()->startOfDay(),
            ]];
        }

        // abs(): diffInDays() en Carbon 3 devuelve la diferencia con
        // signo (negativa si la segunda fecha es anterior), a diferencia
        // de Carbon 2 — acá siempre queremos la distancia en días, sin
        // importar el orden de las fechas guardadas.
        $durationDays = abs($this->starts_at->diffInDays($this->ends_at));
        $teaserOffsetDays = $this->teaser_starts_at ? abs($this->starts_at->diffInDays($this->teaser_starts_at)) : null;

        $candidates = [];
        foreach ([-1, 0, 1] as $yearOffset) {
            $end = Carbon::create(now()->year + $yearOffset, $this->recurring_month, $this->recurring_day)->endOfDay();
            $start = $end->copy()->subDays($durationDays)->startOfDay();
            $teaserStart = $teaserOffsetDays !== null ? $start->copy()->subDays($teaserOffsetDays)->startOfDay() : null;
            $candidates[] = [$start, $end, $teaserStart];
        }

        return $candidates;
    }
}
