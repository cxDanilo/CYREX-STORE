<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('component_type')->nullable()->after('icon');
        });

        // Backfill las categorías que ya existen desde el seeder original —
        // por su slug, que es estable. La categoría de tarjetas gráficas no
        // se toca acá porque en producción quedó creada automáticamente por
        // el importador de WooCommerce con un nombre/slug raro; esa se
        // marca a mano desde el admin.
        $map = [
            'procesadores' => 'cpu',
            'placas-madre' => 'motherboard',
            'memorias-ram' => 'ram',
            'gabinetes' => 'case',
            'refrigeracion' => 'cooler',
            'fuentes-de-poder' => 'psu',
        ];

        foreach ($map as $slug => $type) {
            DB::table('categories')->where('slug', $slug)->update(['component_type' => $type]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('component_type');
        });
    }
};
