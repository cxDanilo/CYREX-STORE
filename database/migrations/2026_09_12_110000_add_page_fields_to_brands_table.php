<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Página de marca (/marcas/{slug}, ej. /marcas/ajazz): la tabla brands
 * ya existía como lista curada de opciones para el <select> de "Marca"
 * en productos (ver 2026_09_12_100000_create_brands_table.php) -- acá
 * se le suma lo necesario para que una marca, opcionalmente, tenga
 * además una landing propia tipo editorial.
 *
 * page_data guarda TODO el contenido de las 10 secciones en un solo
 * JSON (hero, editorial, finder_cards, explore_categories, selection,
 * comparison, content_items, community_posts) en vez de una columna
 * por sección o el sistema de bloques CMS genérico (PageBlock) --
 * quienes-somos ya demostró que ese builder no da abasto para una
 * estructura fija de secciones con animaciones propias (ver el
 * comentario de AboutPageSeeder), y una tabla con una columna por
 * sub-campo de cada sección hubiera sido una migración enorme para
 * contenido que en la práctica siempre se edita junto, sección por
 * sección, desde un único formulario de admin.
 *
 * products.brand sigue siendo el mismo texto libre de siempre -- el
 * link entre una página de marca y "sus" productos sigue siendo por
 * nombre (ver Brand::products()), no por foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('logo')->nullable()->after('slug');
            $table->string('accent_color', 7)->nullable()->after('logo');
            $table->boolean('is_page_published')->default(false)->after('accent_color');
            $table->json('page_data')->nullable()->after('is_page_published');
        });

        // Las marcas ya existentes (cargadas antes de que "slug" existiera)
        // se quedarían con slug null -- eso rompe route('admin.marcas.edit',
        // $brand) y route('brand.show', $brand) porque Brand::getRouteKeyName()
        // usa 'slug'. Se les asigna un slug acá mismo para que sigan
        // funcionando apenas corre esta migración, sin paso manual aparte.
        foreach (DB::table('brands')->whereNull('slug')->get() as $brand) {
            $base = Str::slug($brand->name) ?: 'marca-'.$brand->id;
            $slug = $base;
            $i = 2;
            while (DB::table('brands')->where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
                $slug = $base.'-'.$i++;
            }
            DB::table('brands')->where('id', $brand->id)->update(['slug' => $slug]);
        }

        Schema::table('brands', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['slug', 'logo', 'accent_color', 'is_page_published', 'page_data']);
        });
    }
};
