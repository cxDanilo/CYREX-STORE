<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// Crea/actualiza la página pública "quienes-somos" (Quiénes somos / Misión
// / Visión / Valores) — ver database/seeders/AboutPageSeeder.php. Va en una
// migración (corre sola en el próximo deploy) siguiendo el mismo patrón que
// 2026_09_11_090000_regenerate_product_thumbnails_as_webp.php, en vez de
// requerir un paso manual de "php artisan db:seed" aparte en el servidor.
return new class extends Migration
{
    public function up(): void
    {
        try {
            Artisan::call('db:seed', ['--class' => \Database\Seeders\AboutPageSeeder::class, '--force' => true]);
        } catch (\Throwable $e) {
            // Un fallo acá no puede tirar abajo el deploy entero — la
            // página simplemente no queda creada hasta correrlo a mano.
            Log::error('seed_about_page: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // No reversible con sentido — no-op a propósito.
    }
};
