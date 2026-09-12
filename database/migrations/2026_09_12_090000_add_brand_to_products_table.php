<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Campo de marca, opcional a propósito (ver conversación con el cliente):
// muchos productos no tienen una marca "representada" relevante (cables,
// pasta térmica, accesorios genéricos) — forzarlo obligatorio llevaría a
// cargar cualquier cosa solo para poder guardar. Se completa a mano en
// los productos que sí importan de una marca puntual (Ajazz, Attack
// Shark, Fifine, ATK, Thermalright, etc.), habilitando un filtro
// confiable por marca en la tienda que no dependa de que el nombre del
// producto la mencione textualmente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('category_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }
};
