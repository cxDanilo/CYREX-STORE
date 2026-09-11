<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_groups', function (Blueprint $table) {
            // Antes la campaña arrancaba apenas se creaba — ahora se puede
            // programar para un momento futuro (ej. cargarla hoy para que
            // arranque el viernes a la medianoche). El producto no muestra
            // el precio de oferta hasta llegar a esta fecha, sin necesitar
            // un comando programado aparte: Product::hasActiveOffer() la
            // chequea en el momento, igual que ya hace con ends_at.
            $table->timestamp('starts_at')->nullable()->after('name');
        });

        // Campañas ya existentes: asumimos que ya estaban corriendo desde
        // que se crearon (created_at), para no dejarlas "sin fecha de
        // inicio" de la nada.
        \Illuminate\Support\Facades\DB::table('discount_groups')->whereNull('starts_at')->update([
            'starts_at' => \Illuminate\Support\Facades\DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('discount_groups', function (Blueprint $table) {
            $table->dropColumn('starts_at');
        });
    }
};
