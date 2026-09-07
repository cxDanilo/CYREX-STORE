<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Categorías ADICIONALES de un producto — la real sigue siendo
// products.category_id (de ahí sale el filtro de atributos por tipo de
// componente y las migas de pan). Un producto puede estar en 0 o más
// categorías de acá sin perder ni cambiar la real.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unique(['product_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category');
    }
};
