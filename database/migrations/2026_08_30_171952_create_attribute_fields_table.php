<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos de compat "agregados desde el admin" (Admin → Atributos
     * personalizados), sin tocar config/pc_builder.php. Se combinan con
     * los del config en App\Support\PcBuilderFields::resolved() — o
     * suman un campo a un tipo que ya existe (ej. "panel_mallado" en
     * "case"), o dan de alta un type_key totalmente nuevo si type_label
     * viene cargado.
     */
    public function up(): void
    {
        Schema::create('attribute_fields', function (Blueprint $table) {
            $table->id();
            $table->string('type_key')->index();
            $table->string('type_label')->nullable();
            $table->string('field_key');
            $table->string('label');
            $table->string('field_type');
            $table->json('options')->nullable();
            $table->boolean('shop_filter')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['type_key', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_fields');
    }
};
