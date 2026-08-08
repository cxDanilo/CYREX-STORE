<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('editor')->after('email');
        });

        // Todos los usuarios que ya existían antes de que existiera el
        // concepto de rol tenían acceso total al admin — se los marca
        // 'admin' explícitamente para no dejar a nadie afuera. Los
        // usuarios nuevos de acá en adelante arrancan en 'editor' salvo
        // que un admin los suba de rango desde /admin/usuarios.
        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
