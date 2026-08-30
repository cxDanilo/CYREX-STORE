<?php

use App\Models\Page;
use App\Models\Promotion;
use Illuminate\Database\Migrations\Migration;

/**
 * Las páginas "404", "garantia" y "contacto" (y la promo "ano-nuevo") se
 * cargaron una vez con texto en voseo argentino (NotFoundPageSeeder,
 * WarrantyContactPagesSeeder, PromotionSeeder) — corregir esos seeders no
 * cambia lo que ya está guardado en la base real, así que esto actualiza
 * los textos existentes a español neutro. Solo pisa el valor si sigue
 * siendo EXACTAMENTE el texto viejo — si el admin ya lo editó a mano
 * desde entonces, esto no lo toca.
 */
return new class extends Migration
{
    private const REPLACEMENTS = [
        '404' => [
            'hero_simple' => [
                'subtitulo' => [
                    'El link que seguiste no existe o se movió de lugar. Probá volver al inicio o directo a la tienda.',
                    'El link que seguiste no existe o se movió de lugar. Prueba volver al inicio o directo a la tienda.',
                ],
            ],
        ],
        'garantia' => [
            'hero_simple' => [
                'subtitulo' => [
                    'Consultá el alcance, los plazos, los requisitos y las exclusiones de garantía para los productos comprados en Cyrex Store.',
                    'Consulta el alcance, los plazos, los requisitos y las exclusiones de garantía para los productos comprados en Cyrex Store.',
                ],
            ],
            'texto_libre' => [
                'texto' => [
                    '¿Todavía tenés dudas sobre tu garantía? Escribinos por WhatsApp, por mail a storecyrex@gmail.com, o hablá directo con nuestro asesor especializado al +591 72768984.',
                    '¿Todavía tienes dudas sobre tu garantía? Escríbenos por WhatsApp, por mail a storecyrex@gmail.com, o habla directo con nuestro asesor especializado al +591 72768984.',
                ],
            ],
            'cta_whatsapp' => [
                'texto' => [
                    'Consultanos por WhatsApp sobre tu garantía',
                    'Consúltanos por WhatsApp sobre tu garantía',
                ],
            ],
        ],
        'contacto' => [
            'hero_simple' => [
                'subtitulo' => [
                    'Elegí tu sucursal, hablá con un asesor y encontrá nuestra ubicación sin perder tiempo.',
                    'Elige tu sucursal, habla con un asesor y encuentra nuestra ubicación sin perder tiempo.',
                ],
            ],
            'formulario' => [
                'titulo' => [
                    'Escribinos directo',
                    'Escríbenos directo',
                ],
            ],
        ],
    ];

    private const META_DESCRIPTIONS = [
        'garantia' => [
            'Conocé el alcance, los plazos, los requisitos y las exclusiones de la garantía de los productos que comprás en Cyrex Store.',
            'Conoce el alcance, los plazos, los requisitos y las exclusiones de la garantía de los productos que compras en Cyrex Store.',
        ],
        'contacto' => [
            'Escribinos por WhatsApp o dejanos tu consulta — te respondemos rápido sobre productos, pedidos o garantías.',
            'Escríbenos por WhatsApp o déjanos tu consulta — te respondemos rápido sobre productos, pedidos o garantías.',
        ],
    ];

    public function up(): void
    {
        foreach (self::META_DESCRIPTIONS as $slug => [$old, $new]) {
            Page::where('slug', $slug)->where('meta_description', $old)->update(['meta_description' => $new]);
        }

        foreach (self::REPLACEMENTS as $slug => $byType) {
            $page = Page::where('slug', $slug)->first();

            if (! $page) {
                continue;
            }

            foreach ($page->blocks as $block) {
                $fields = $byType[$block->type] ?? null;

                if (! $fields) {
                    continue;
                }

                $data = $block->data ?? [];
                $changed = false;

                foreach ($fields as $key => [$old, $new]) {
                    if (($data[$key] ?? null) === $old) {
                        $data[$key] = $new;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $block->update(['data' => $data]);
                }
            }

            // La página "contacto" tiene además el mensaje de "próximamente"
            // de una sucursal, adentro del array de items del bloque
            // "sucursales" — no encaja en el patrón campo->valor de arriba.
            if ($slug === 'contacto') {
                $sucursales = $page->blocks->firstWhere('type', 'sucursales');

                if ($sucursales) {
                    $data = $sucursales->data ?? [];
                    $changed = false;
                    $old = 'Una nueva sucursal está en camino. Si viste esto, ya sabés algo antes que todos.';
                    $new = 'Una nueva sucursal está en camino. Si viste esto, ya sabes algo antes que todos.';

                    foreach ($data['items'] ?? [] as $i => $item) {
                        if (($item['mensaje_proximamente'] ?? null) === $old) {
                            $data['items'][$i]['mensaje_proximamente'] = $new;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $sucursales->update(['data' => $data]);
                    }
                }
            }
        }

        $promo = Promotion::where('slug', 'ano-nuevo')->first();

        if ($promo && $promo->teaser_text === 'Cerrá el año con Cyrex') {
            $promo->update(['teaser_text' => 'Cierra el año con Cyrex']);
        }
    }

    public function down(): void
    {
        // Intencionalmente sin rollback — volver al voseo no tiene sentido.
    }
};
