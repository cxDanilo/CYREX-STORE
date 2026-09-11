<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// Reemplaza el contenido de "quienes-somos" por la versión con scroll
// animado (ver database/seeders/AboutPageSeeder.php) — la primera versión
// (2026_09_11_110000_seed_about_page.php) usaba bloques genéricos del CMS
// planos, sin ninguna animación. Misma razón que esa migración: corre
// sola en el próximo deploy en vez de requerir un paso manual aparte.
return new class extends Migration
{
    public function up(): void
    {
        try {
            Artisan::call('db:seed', ['--class' => \Database\Seeders\AboutPageSeeder::class, '--force' => true]);
        } catch (\Throwable $e) {
            Log::error('update_about_page_design: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // No reversible con sentido — no-op a propósito.
    }
};
