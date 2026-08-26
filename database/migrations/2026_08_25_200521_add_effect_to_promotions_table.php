<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Efecto ambiente de fondo (nieve, confeti, etc.) mientras la
            // promo está ACTIVA — 'none' es el default, no todas necesitan
            // uno. Ver public/js/promo-effects.js para las opciones reales.
            $table->string('effect')->default('none')->after('show_as_modal');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('effect');
        });
    }
};
