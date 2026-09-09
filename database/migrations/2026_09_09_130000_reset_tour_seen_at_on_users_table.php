<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// El tour de bienvenida se amplió (ahora también enseña a crear y
// editar un producto, no solo el Dashboard) — se resetea para que
// todas las cuentas existentes lo vean al menos una vez más, en vez
// de quedar marcado como "visto" con la versión vieja y corta.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update(['tour_seen_at' => null]);
    }

    public function down(): void
    {
        // No reversible con sentido (no hay forma de saber quién ya
        // lo había visto antes del reset) -- no-op a propósito.
    }
};
