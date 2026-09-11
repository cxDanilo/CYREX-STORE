<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// La foto de "quienes-somos" estaba metida como texto placeholder dentro
// del bloque html_libre -- sin ningun campo de "subir imagen" real en el
// editor. AboutPageSeeder ahora separa esa foto en su propio bloque
// 'imagen' (el mismo tipo que ya se usa en el resto del sitio, con su
// selector de medios normal). Misma razon que las migraciones anteriores
// de esta pagina: corre sola en el proximo deploy.
return new class extends Migration
{
    public function up(): void
    {
        try {
            Artisan::call('db:seed', ['--class' => \Database\Seeders\AboutPageSeeder::class, '--force' => true]);
        } catch (\Throwable $e) {
            Log::error('split_about_page_photo_block: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // No reversible con sentido — no-op a propósito.
    }
};
