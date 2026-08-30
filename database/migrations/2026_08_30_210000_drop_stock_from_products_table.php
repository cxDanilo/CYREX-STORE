<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El negocio no lleva inventario acá (usa un POS aparte, ver el
 * comentario de la migración que agregó is_sold_out) — el número de
 * stock nunca se usó como inventario real, solo quedaba como campo
 * obligatorio sin sentido en el formulario de productos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0);
        });
    }
};
