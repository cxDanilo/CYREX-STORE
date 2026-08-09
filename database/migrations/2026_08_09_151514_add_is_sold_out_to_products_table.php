<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Marca explícita del admin, independiente del número de
            // stock (que no se usa como inventario real — el negocio
            // lleva su inventario en un POS aparte). sold_out_at queda
            // fijo desde que se marcó, para poder pasar el producto a
            // privado automáticamente pasados unos días.
            $table->boolean('is_sold_out')->default(false)->after('stock');
            $table->timestamp('sold_out_at')->nullable()->after('is_sold_out');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_sold_out', 'sold_out_at']);
        });
    }
};
