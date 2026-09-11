<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// ImageOptimizer ahora guarda la miniatura de cada producto en WebP en vez
// del mismo formato que el original (típicamente PNG, que para una foto de
// producto pesa varias veces más a igual calidad) — ver ImageOptimizer.php
// y Product::getImageThumbUrlAttribute(). Ese cambio de código, solo,
// dejaría a todos los productos YA subidos sin su miniatura .webp en disco
// (siguen teniendo la vieja thumb_*.png, que el accessor ya no busca), así
// que hasta que esto corra, el sitio entero caería a servir la foto
// ORIGINAL de hasta 2000px en vez de una miniatura — peor que antes. Por
// eso el reprocesamiento va en una migración (corre solo en el próximo
// deploy) y no queda como un paso manual aparte.
return new class extends Migration
{
    public function up(): void
    {
        try {
            Artisan::call('products:regenerate-thumbnails');
        } catch (\Throwable $e) {
            // Un fallo acá (ej. GD no disponible en el server) no puede
            // tirar abajo el deploy entero — el catálogo simplemente
            // sigue sirviendo el original hasta que se corra a mano.
            Log::error('regenerate_product_thumbnails_as_webp: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // No reversible con sentido — no-op a propósito.
    }
};
