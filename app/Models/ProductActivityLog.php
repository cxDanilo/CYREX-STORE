<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductActivityLog extends Model
{
    public $timestamps = false;

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
}
