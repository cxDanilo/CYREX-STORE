<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Crea/actualiza la página "quienes-somos" (Quiénes somos / Misión /
 * Visión / Valores), contada como una historia corta en vez de cuatro
 * bloques largos de texto seguidos. Busca la página por slug (no por
 * id) para funcionar igual en cualquier entorno. Se puede correr más
 * de una vez sin duplicar nada.
 */
class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'quienes-somos'],
            ['title' => 'Quiénes Somos', 'status' => 'draft', 'show_in_footer' => true, 'footer_sort_order' => 5]
        );

        $page->update([
            'title' => 'Quiénes Somos',
            'meta_title' => '',
            'meta_description' => 'Conoce la historia de Cyrex Store, nuestra misión, nuestra visión y los valores que nos mueven todos los días.',
            'status' => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);

        $page->blocks()->delete();

        $blocks = [
            ['type' => 'hero_simple', 'data' => [
                'eyebrow' => 'Quiénes somos',
                'titulo' => 'Todo comenzó viendo una oportunidad donde otros solo veían un mercado.',
                'titulo_destacado' => '',
                'subtitulo' => 'CYREX Store nació después de la pandemia, en medio de una etapa donde todo estaba cambiando. Vimos un espacio que todavía podía hacerse diferente: un lugar donde comprar tecnología no fuera simplemente elegir un producto, pagar y marcharse. Queríamos construir algo más — un espacio donde cada persona pudiera recibir una recomendación honesta, encontrar el equipo adecuado para lo que realmente necesita, y disfrutar el proceso de armar, mejorar o comprar su primera PC. Desde entonces crecimos junto a nuestros clientes, aprendiendo y formando una comunidad alrededor de algo que nos apasiona: la tecnología.',
                'cta_label' => '',
                'cta_url' => '',
                'tamano' => 'grande',
            ]],

            ['type' => 'separador', 'data' => ['tamano' => 'grande']],

            ['type' => 'titulo', 'data' => ['texto' => 'Nuestra misión', 'tamano' => 'chico']],
            ['type' => 'titulo', 'data' => ['texto' => 'Transformar la manera de vivir la tecnología.', 'tamano' => 'grande']],

            ['type' => 'separador', 'data' => ['tamano' => 'grande']],

            ['type' => 'titulo', 'data' => ['texto' => 'Nuestra visión', 'tamano' => 'chico']],
            ['type' => 'titulo', 'data' => ['texto' => 'Ser una marca que las personas recuerden por cómo las hicimos sentir.', 'tamano' => 'grande']],

            ['type' => 'separador', 'data' => ['tamano' => 'grande']],

            ['type' => 'titulo', 'data' => ['texto' => 'Nuestros valores', 'tamano' => 'mediano']],
            ['type' => 'cards', 'data' => [
                'items' => [
                    ['titulo' => 'Pasión', 'texto' => 'Nos mueve la tecnología.'],
                    ['titulo' => 'Confianza', 'texto' => 'Recomendamos como nos gustaría que nos recomendaran.'],
                    ['titulo' => 'Experiencia', 'texto' => 'Cada detalle importa.'],
                    ['titulo' => 'Comunidad', 'texto' => 'Crecemos junto a quienes confían en nosotros.'],
                ],
            ]],

            ['type' => 'cta_whatsapp', 'data' => ['texto' => 'Hablá con nosotros por WhatsApp']],
        ];

        foreach ($blocks as $i => $block) {
            $page->blocks()->create([
                'type' => $block['type'],
                'data' => $block['data'],
                'sort_order' => $i,
            ]);
        }

        $this->command?->info("Página \"quienes-somos\" creada/actualizada con ".count($blocks).' bloques.');
    }
}
