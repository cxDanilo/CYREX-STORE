<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pc_builder_options', function (Blueprint $table) {
            $table->id();
            // "group" identifica la lista a la que pertenece (socket,
            // ram_type, form_factor, radiator_size, storage_type, gpu_tier)
            // — son los mismos grupos que ya usan los campos de
            // config/pc_builder.php, ahora editables sin tocar código.
            $table->string('group');
            $table->string('value');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['group', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pc_builder_options');
    }
};
