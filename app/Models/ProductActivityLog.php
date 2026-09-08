<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductActivityLog extends Model
{
    public $timestamps = false;

    // Nombres de columna -> texto que ve el admin en el historial (ver
    // describeChange() más abajo). Lo que no está acá se muestra con
    // guiones bajos cambiados por espacios y la primera letra en
    // mayúscula, para no dejar un campo nuevo sin traducir del todo.
    private const FIELD_LABELS = [
        'name' => 'Nombre', 'slug' => 'Slug', 'description' => 'Descripción',
        'price' => 'Precio', 'currency' => 'Moneda', 'sku' => 'SKU',
        'category_id' => 'Categoría', 'status' => 'Estado', 'image' => 'Imagen',
        'has_variants' => 'Tiene variantes', 'specs' => 'Especificaciones técnicas',
        'compat' => 'Compatibilidad', 'is_sold_out' => 'Agotado',
        'sold_out_at' => 'Fecha de agotado', 'promotion_id' => 'Promoción',
        'offer_price' => 'Precio de oferta', 'offer_selected' => 'En oferta',
        'discount_group_id' => 'Campaña de descuento',
    ];

    // Columnas booleanas cuyo valor "crudo" (getAttributes(), antes de
    // que el cast de Eloquent lo convierta) llega como '1'/'0' en vez
    // de true/false — sin esta lista se verían como "1 → 0" en vez de
    // "Sí → No".
    private const BOOLEAN_FIELDS = ['is_sold_out', 'has_variants', 'offer_selected'];

    protected $fillable = [
        'product_id', 'user_id', 'user_name', 'product_name', 'action', 'changes', 'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(Product $product, string $action, array $changes = []): void
    {
        static::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'Sistema',
            'product_name' => $product->name,
            'action' => $action,
            'changes' => $changes ?: null,
            'created_at' => now(),
        ]);
    }

    /**
     * Registra solo los campos que Eloquent detectó como realmente
     * cambiados tras un update() — $before es el snapshot de atributos
     * tomado ANTES de guardar (getOriginal() ya no sirve después del
     * save(), Eloquent lo sincroniza con los valores nuevos).
     */
    public static function logFieldChanges(Product $product, array $before): void
    {
        $changes = [];

        foreach ($product->getChanges() as $key => $newValue) {
            if ($key === 'updated_at') {
                continue;
            }

            $changes[$key] = ['antes' => $before[$key] ?? null, 'despues' => $newValue];
        }

        if (! empty($changes)) {
            static::record($product, 'updated', $changes);
        }
    }

    public static function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Convierte un antes/después crudo en filas legibles para el
     * admin: [['label' => ..., 'before' => ..., 'after' => ...], ...].
     * Campos como compat/specs se guardan como JSON — mostrar esas dos
     * cadenas enteras (truncadas) una al lado de la otra es ilegible,
     * casi siempre cambia UN solo valor adentro. Acá se decodifican
     * ambos lados y se compara clave por clave, devolviendo una fila
     * por cada valor que de verdad cambió (ej. "Marca: Intel →
     * Nvidia") en vez del bloque entero — por eso siempre es un
     * array de filas, aunque para un campo simple sea una sola.
     */
    public static function describeChange(?string $before, ?string $after, ?string $field = null): array
    {
        $beforeArr = self::decodeIfJsonObject($before);
        $afterArr = self::decodeIfJsonObject($after);

        if ($beforeArr === null && $afterArr === null) {
            $isBoolean = $field && in_array($field, self::BOOLEAN_FIELDS, true);

            return [['label' => null, 'before' => self::formatScalar($before, $isBoolean), 'after' => self::formatScalar($after, $isBoolean)]];
        }

        $beforeArr ??= [];
        $afterArr ??= [];
        $keys = array_unique(array_merge(array_keys($beforeArr), array_keys($afterArr)));
        $rows = [];

        foreach ($keys as $key) {
            $b = $beforeArr[$key] ?? null;
            $a = $afterArr[$key] ?? null;
            if ($b == $a) {
                continue;
            }
            $rows[] = [
                'label' => ucfirst(str_replace('_', ' ', (string) $key)),
                'before' => self::formatScalar($b),
                'after' => self::formatScalar($a),
            ];
        }

        return $rows ?: [['label' => null, 'before' => null, 'after' => null]];
    }

    private static function decodeIfJsonObject(?string $value): ?array
    {
        if (! is_string($value) || ! in_array($value[0] ?? '', ['{', '['], true)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function formatScalar(mixed $value, bool $isBoolean = false): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value) || ($isBoolean && in_array($value, ['0', '1', 0, 1], true))) {
            return $value == true ? 'Sí' : 'No';
        }
        if (is_array($value)) {
            return implode(', ', array_map(fn ($v) => (string) $v, $value)) ?: '—';
        }

        return \Illuminate\Support\Str::limit((string) $value, 60);
    }
}
