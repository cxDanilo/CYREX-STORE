<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_build_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_build_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            // nullOnDelete a propósito: si el producto se borra del
            // catálogo más adelante, el armado publicado sigue mostrando
            // la pieza (con los datos ya snapshoteados abajo) en vez de
            // desaparecer o romperse.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_image_url')->nullable();
            $table->decimal('unit_price_usd', 10, 2);
            $table->unsignedTinyInteger('qty')->default(1);
            $table->json('compat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_build_items');
    }
};
