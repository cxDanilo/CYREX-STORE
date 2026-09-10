<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_builds', function (Blueprint $table) {
            $table->id();
            // Identificador público (URL /armados/{slug}) en vez del id
            // autoincremental — no hay cuentas de visitante todavía, así
            // que este link al azar es la única forma de "ser dueño" del
            // propio armado (compartirlo, volver a verlo) sin login.
            $table->string('slug', 20)->unique();
            $table->string('visitor_name', 60);
            // pending -> recién publicado, esperando revisión del admin.
            // approved -> aparece en la galería pública.
            // rejected -> el admin lo descartó (spam, nombre feo, etc.).
            $table->string('status', 20)->default('pending');
            $table->string('platform', 10)->nullable();
            $table->unsignedTinyInteger('ram_qty')->default(1);
            $table->boolean('wants_assembly')->default(false);
            $table->decimal('assembly_fee_usd', 8, 2)->nullable();
            // Snapshot del total en el momento de publicar — el precio de
            // un producto (o el tipo de cambio) puede cambiar después, y
            // un armado ya publicado no debería moverse de precio solo.
            $table->decimal('total_usd', 10, 2);
            $table->decimal('rate', 8, 4);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_builds');
    }
};
