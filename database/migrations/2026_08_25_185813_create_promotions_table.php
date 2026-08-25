<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('banner_text');
            $table->string('teaser_text')->nullable();
            $table->date('teaser_starts_at')->nullable();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('discount_label')->nullable();
            // Fechas como Navidad o Día de la Madre vuelven todos los años —
            // recurring_month/recurring_day marcan el día del evento en sí,
            // y se reproyecta sobre el año actual en vez de reprogramar la
            // promo cada vez (ver Promotion::windowCandidates()).
            $table->boolean('is_recurring')->default(false);
            $table->unsignedTinyInteger('recurring_month')->nullable();
            $table->unsignedTinyInteger('recurring_day')->nullable();
            // Reservado para 1-2 fechas grandes al año (Navidad) — nunca
            // convive con la barra de anuncio al mismo tiempo.
            $table->boolean('show_as_modal')->default(false);
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
