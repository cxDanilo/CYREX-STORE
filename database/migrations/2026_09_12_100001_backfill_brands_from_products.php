<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Los productos que ya tenían "brand" cargado a mano (ej. "ajazz") antes
// de que existiera la tabla brands no deben desaparecer del desplegable —
// se copian una sola vez como fila inicial de brands. Corridas futuras no
// duplican nada (insertOrIgnore por el unique en brands.name).
return new class extends Migration
{
    public function up(): void
    {
        $names = DB::table('products')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand');

        $now = now();

        foreach ($names as $name) {
            DB::table('brands')->insertOrIgnore([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // No reversible con sentido -- no-op a propósito.
    }
};
