<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Reemplaza el contenido placeholder ("[Pendiente de completar]") de las
 * páginas "garantia" y "contacto" con el contenido real, adaptado de
 * cyrexstore.com — y agrega "Contacto" al menú del header. Busca las
 * páginas por slug (no por id) para funcionar igual en cualquier entorno.
 * Se puede correr más de una vez sin duplicar nada.
 */
class WarrantyContactPagesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGarantia();
        $this->seedContacto();
        $this->addContactoToHeaderNav();
    }

    private function seedGarantia(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'garantia'],
            ['title' => 'Garantía', 'status' => 'draft', 'show_in_footer' => true, 'footer_sort_order' => 10]
        );

        $page->update([
            'title' => 'Condiciones de Garantía',
            'meta_title' => '',
            'meta_description' => 'Conocé el alcance, los plazos, los requisitos y las exclusiones de la garantía de los productos que comprás en Cyrex Store.',
            'status' => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);

        $page->blocks()->delete();

        $blocks = [
            ['type' => 'hero_simple', 'data' => [
                'eyebrow' => '',
                'titulo' => 'Condiciones de Garantía',
                'titulo_destacado' => '',
                'subtitulo' => 'Consultá el alcance, los plazos, los requisitos y las exclusiones de garantía para los productos comprados en Cyrex Store.',
                'cta_label' => '',
                'cta_url' => '',
                'tamano' => 'estandar',
            ]],
            ['type' => 'titulo', 'data' => [
                'texto' => 'Plazos de garantía por categoría',
                'tamano' => 'mediano',
            ]],
            ['type' => 'cards', 'data' => [
                'items' => [
                    ['titulo' => 'Equipos armados por Cyrex', 'texto' => '12 meses de garantía cuando todos los componentes son de la tienda.'],
                    ['titulo' => 'Equipos armados por terceros', 'texto' => '3 meses de garantía por cada componente, de forma individual.'],
                    ['titulo' => 'Componentes de PC', 'texto' => '3 meses contra defectos de fabricación — procesadores, tarjetas gráficas, placas madre, RAM, almacenamiento, fuentes de poder y refrigeración.'],
                    ['titulo' => 'Periféricos', 'texto' => '3 meses en general. Redragon: 12 meses. Corsair: 6 meses. Deepcool: 3 meses.'],
                    ['titulo' => 'Monitores', 'texto' => '3 meses contra defectos de fabricación. Hasta 5 píxeles muertos se considera dentro de las tolerancias normales.'],
                    ['titulo' => 'Accesorios y consumibles', 'texto' => 'Cobertura inmediata a la entrega — no se aceptan reclamos después de retirar y verificar el producto (cables, adaptadores, pads, pasta térmica, etc.).'],
                    ['titulo' => 'Productos con servicio técnico oficial', 'texto' => 'Marcas con servicio propio (ej. Xiaomi) se gestionan directo con el centro autorizado del fabricante — te damos el contacto, pero la tienda no recibe el producto.'],
                ],
            ]],
            ['type' => 'titulo', 'data' => [
                'texto' => 'Preguntas frecuentes sobre la garantía',
                'tamano' => 'mediano',
            ]],
            ['type' => 'faq', 'data' => [
                'items' => [
                    [
                        'pregunta' => '¿Qué cubre la garantía?',
                        'respuesta' => 'Cubre defectos de fabricación o fallas de funcionamiento atribuibles al fabricante. No cubre daños por mal uso, instalación incorrecta o manipulación indebida, factores externos o desgaste normal, ni daños indirectos (pérdida de datos, lucro cesante, etc.).',
                    ],
                    [
                        'pregunta' => '¿Qué necesito para solicitar la garantía?',
                        'respuesta' => "Comprobante de compra original\nProducto dentro del plazo de garantía\nCaja original y accesorios completos\nNúmeros de serie y sellos intactos\nProducto sin daño físico evidente\n\nSi falta alguno de estos puntos, el reclamo puede ser rechazado.",
                    ],
                    [
                        'pregunta' => '¿Cómo es el procedimiento?',
                        'respuesta' => "1. Recepción del producto en la tienda\n2. Revisión técnica inicial\n3. Diagnóstico del problema\n4. Envío al distribuidor autorizado o al fabricante\n\nSegún el diagnóstico, el resultado puede ser reparación, reemplazo por una unidad nueva, reemplazo por un modelo equivalente, o devolución de dinero si no hay stock disponible.",
                    ],
                    [
                        'pregunta' => '¿Cuánto tiempo tarda el proceso?',
                        'respuesta' => 'El diagnóstico inicial en tienda toma de 1 a 3 días hábiles. Si el proceso pasa al distribuidor autorizado o al fabricante, puede tomar de 1 a 4 semanas, y a veces más si el fabricante pide procesos adicionales. Cyrex Store actúa como intermediario y no puede garantizar tiempos exactos cuando dependen de terceros.',
                    ],
                    [
                        'pregunta' => '¿Cómo es el diagnóstico técnico?',
                        'respuesta' => 'Todo producto en garantía pasa por una revisión técnica inicial. Si no se encuentra un defecto de fabricación, el reclamo puede ser rechazado y el cliente retira el producto. No se consideran defecto de fábrica: configuración incorrecta, incompatibilidad de hardware, problemas de software o instalación incorrecta.',
                    ],
                    [
                        'pregunta' => '¿Qué anula la garantía?',
                        'respuesta' => "Golpes o caídas\nHumedad o contacto con líquidos\nExceso de polvo o insectos\nCorrosión u oxidación\nSobrecalentamiento por mal mantenimiento\nDaño eléctrico o uso de una fuente de poder defectuosa\nInstalación incorrecta\nReparación o manipulación por terceros\nSellos de garantía rotos\nModificaciones de BIOS/firmware no oficiales\nOverclocking fuera de las especificaciones del fabricante",
                    ],
                    [
                        'pregunta' => '¿La tienda garantiza compatibilidad con piezas externas?',
                        'respuesta' => 'No. Cyrex Store no garantiza compatibilidad con productos, piezas o accesorios adquiridos fuera de la tienda — verificar la compatibilidad de hardware externo es responsabilidad del cliente.',
                    ],
                    [
                        'pregunta' => '¿Qué pasa con mis datos guardados?',
                        'respuesta' => 'La tienda no se hace responsable por la pérdida de datos en discos entregados para diagnóstico o garantía. Te recomendamos hacer un respaldo antes de entregar el equipo.',
                    ],
                    [
                        'pregunta' => '¿Un reemplazo por garantía renueva el plazo?',
                        'respuesta' => 'No. El plazo de garantía no se reinicia — sigue contando desde la fecha de compra original. El reemplazo puede ser del mismo modelo, un producto equivalente, o uno de especificaciones similares si el original ya se descontinuó.',
                    ],
                    [
                        'pregunta' => '¿Quién se encarga del transporte del producto?',
                        'respuesta' => 'El cliente es responsable de llevar el producto al punto de recepción y de retirarlo una vez terminado el proceso. La tienda no cubre costos de transporte o envío durante la gestión de garantía.',
                    ],
                    [
                        'pregunta' => '¿Hasta dónde llega la responsabilidad de la tienda?',
                        'respuesta' => 'La tienda no responde por daños indirectos como pérdida de información, pérdida de ingresos, interrupción de actividades, daños a otros equipos conectados o incompatibilidades. Su responsabilidad se limita a: diagnóstico, reparación, reemplazo equivalente, o devolución de dinero, según corresponda.',
                    ],
                    [
                        'pregunta' => '¿Qué implica comprar en Cyrex Store?',
                        'respuesta' => 'Al comprar en Cyrex Store, el cliente confirma que leyó y aceptó estas condiciones de garantía.',
                    ],
                ],
            ]],
            ['type' => 'texto_libre', 'data' => [
                'texto' => '¿Todavía tenés dudas sobre tu garantía? Escribinos por WhatsApp, por mail a storecyrex@gmail.com, o hablá directo con nuestro asesor especializado al +591 72768984.',
            ]],
            ['type' => 'cta_whatsapp', 'data' => [
                'texto' => 'Consultanos por WhatsApp sobre tu garantía',
            ]],
        ];

        foreach ($blocks as $i => $block) {
            $page->blocks()->create([
                'type' => $block['type'],
                'data' => $block['data'],
                'sort_order' => $i,
            ]);
        }

        $this->command?->info("Página \"garantia\" actualizada con ".count($blocks).' bloques.');
    }

    private function seedContacto(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'contacto'],
            ['title' => 'Contacto', 'status' => 'draft', 'show_in_footer' => true, 'footer_sort_order' => 20]
        );

        $page->update([
            'title' => 'Contacto',
            'meta_title' => '',
            'meta_description' => 'Escribinos por WhatsApp o dejanos tu consulta — te respondemos rápido sobre productos, pedidos o garantías.',
            'status' => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);

        $page->blocks()->delete();

        $blocks = [
            ['type' => 'hero_simple', 'data' => [
                'eyebrow' => 'Atención Cyrex Store',
                'titulo' => 'Contáctanos fácil y rápido',
                'titulo_destacado' => '',
                'subtitulo' => 'Elegí tu sucursal, hablá con un asesor y encontrá nuestra ubicación sin perder tiempo.',
                'cta_label' => 'Ver sucursales',
                'cta_url' => '#sucursales',
                'cta2_label' => 'storecyrex@gmail.com',
                'cta2_url' => 'mailto:storecyrex@gmail.com',
                'personaje_url' => asset('favicon-512.png'),
                'tamano' => 'estandar',
            ]],
            ['type' => 'sucursales', 'data' => [
                'items' => [
                    [
                        'nombre' => 'Sucursal Central', 'ciudad' => 'Cochabamba', 'direccion' => 'Cyrex Store, Cochabamba', 'proximamente' => '',
                        'mapa_embed' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3232.3122885445905!2d-66.14946710600383!3d-17.39324160579333!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x93e373d6655403eb%3A0xd59bb6a5e274f63e!2sCYREX%20STORE!5e1!3m2!1ses-419!2sbo!4v1786297731336!5m2!1ses-419!2sbo" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
                        'asesores' => [
                            ['nombre' => 'Jonatan', 'cargo' => 'Asesor Antezana', 'whatsapp' => ''],
                            ['nombre' => 'Santiago', 'cargo' => 'Asesor Antezana', 'whatsapp' => ''],
                        ],
                    ],
                    [
                        'nombre' => 'Sucursal América', 'ciudad' => 'Cochabamba', 'direccion' => '', 'proximamente' => '',
                        'asesores' => [
                            ['nombre' => 'Miguel', 'cargo' => 'Asesor América', 'whatsapp' => ''],
                            ['nombre' => 'Alan', 'cargo' => 'Asesor América', 'whatsapp' => ''],
                        ],
                    ],
                    [
                        'nombre' => 'Sucursal Santa Cruz', 'ciudad' => 'Santa Cruz', 'direccion' => '', 'proximamente' => '',
                        'asesores' => [
                            ['nombre' => 'Milena', 'cargo' => 'Sucursal Santa Cruz', 'whatsapp' => ''],
                            ['nombre' => 'Aldair', 'cargo' => 'Sucursal Santa Cruz', 'whatsapp' => ''],
                        ],
                    ],
                    [
                        'nombre' => 'La Paz', 'ciudad' => '', 'direccion' => '', 'proximamente' => 'si',
                        'mensaje_proximamente' => 'Una nueva sucursal está en camino. Si viste esto, ya sabés algo antes que todos.',
                        'asesores' => [],
                    ],
                ],
            ]],
            ['type' => 'formulario', 'data' => [
                'titulo' => 'Escribinos directo',
                'boton_texto' => 'Enviar por WhatsApp',
            ]],
        ];

        foreach ($blocks as $i => $block) {
            $page->blocks()->create([
                'type' => $block['type'],
                'data' => $block['data'],
                'sort_order' => $i,
            ]);
        }

        $this->command?->info("Página \"contacto\" actualizada con ".count($blocks).' bloques.');
    }

    private function addContactoToHeaderNav(): void
    {
        $menu = Menu::where('key', 'header_nav')->first();
        $contacto = Page::where('slug', 'contacto')->first();

        if (! $menu || ! $contacto) {
            return;
        }

        $exists = $menu->items()->where('page_id', $contacto->id)->exists();

        if ($exists) {
            return;
        }

        MenuItem::create([
            'menu_id' => $menu->id,
            'page_id' => $contacto->id,
            'label' => 'Contacto',
            'url' => null,
            'sort_order' => $menu->items()->max('sort_order') + 1,
        ]);

        $this->command?->info('"Contacto" agregado al menú del header.');
    }
}
