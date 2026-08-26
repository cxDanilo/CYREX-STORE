<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // CSS libre que Danilo puede cargar por fecha (ej. cambiar
            // colores de algún elemento puntual para Halloween) — se
            // inyecta en un <style> propio solo mientras la promo está
            // ACTIVA, ver layouts/app.blade.php.
            $table->text('custom_css')->nullable()->after('effect');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('custom_css');
        });
    }
};
