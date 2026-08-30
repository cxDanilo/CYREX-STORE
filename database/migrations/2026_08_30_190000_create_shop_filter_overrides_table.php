<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite prender/apagar el filtro de tienda de un campo YA INCORPORADO
 * (ej. Almacenamiento -> Tipo) desde el admin, sin editar
 * config/pc_builder.php a mano. Solo aplica a campos que ya existen en
 * config — los campos nuevos (tabla attribute_fields) ya tienen su
 * propio shop_filter editable en su propio formulario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_filter_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('type_key')->index();
            $table->string('field_key');
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            $table->unique(['type_key', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_filter_overrides');
    }
};
