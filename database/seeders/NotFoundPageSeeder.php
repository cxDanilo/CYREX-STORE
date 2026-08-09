<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Convierte el texto de la página 404 (hasta ahora hardcodeado en
 * resources/views/errors/404.blade.php) en una Página real, para que el
 * título/subtítulo/botones se puedan editar desde Admin → Páginas sin
 * tocar código. El slug "404" está reservado (ver Admin\PageController)
 * así ningún admin puede crear otra página que choque con esta.
 */
class NotFoundPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => '404'],
            ['title' => '404 — Página no encontrada', 'status' => 'draft', 'show_in_footer' => false]
        );

        $page->update([
            'title' => '404 — Página no encontrada',
            'status' => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);

        $page->blocks()->delete();

        $page->blocks()->create([
            'type' => 'hero_simple',
            'sort_order' => 0,
            'data' => [
                'eyebrow' => 'Error 404',
                'titulo' => 'Esta página se quedó sin señal',
                'subtitulo' => 'El link que seguiste no existe o se movió de lugar. Probá volver al inicio o directo a la tienda.',
                'cta_label' => 'Volver al inicio',
                'cta_url' => '/',
                'cta2_label' => 'Ver tienda',
                'cta2_url' => '/tienda',
            ],
        ]);

        $this->command?->info('Página "404" actualizada.');
    }
}
