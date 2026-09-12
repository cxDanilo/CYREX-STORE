<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lista de marcas que el admin gestiona a mano (Admin -> Marcas), para
// elegirlas de un desplegable al cargar un producto en vez de escribirlas
// cada vez. products.brand SIGUE SIENDO texto libre (no una foreign key) a
// propósito: el filtro por marca de la tienda, el sitemap y el bloque de
// CMS "Productos" ya trabajan comparando ese texto, y no hay necesidad de
// migrar todo eso a una relación solo para tener un desplegable en el
// formulario. Esta tabla es la fuente de las OPCIONES del desplegable,
// nada más — borrar una marca de acá no toca los productos que ya la
// tengan cargada.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
