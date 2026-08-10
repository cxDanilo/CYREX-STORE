<?php

// Fuente única de verdad para "Arma tu PC": qué tipos de componente existen
// y qué campos de compatibilidad tiene cada uno. La usan el formulario de
// productos del admin (para saber qué inputs mostrar según la categoría) y
// el motor de compatibilidad del armador público.
//
// 'options' => 'dynamic:<grupo>' significa que la LISTA de valores no vive
// acá sino en la tabla pc_builder_options (Admin → Compatibilidad) — así
// se puede agregar un socket nuevo (ej. LGA1851 para Core Ultra) o un tipo
// de RAM nuevo (DDR6) sin tocar código. App\Support\PcBuilderFields es
// quien reemplaza ese string por las opciones reales antes de llegar a
// cualquier vista. La estructura del campo (qué campos tiene cada tipo de
// pieza, y si es select/number/checkboxes) sigue viviendo acá.

return [

    // Orden = orden de los pasos del asistente "Arma tu PC" en el sitio
    // público (además de cómo se listan en el admin) — pensado como una
    // secuencia natural de armado, no alfabético.
    'component_types' => [
        'cpu' => 'Procesador',
        'motherboard' => 'Placa madre',
        'ram' => 'Memoria RAM',
        'storage' => 'Almacenamiento',
        'gpu' => 'Tarjeta gráfica',
        'psu' => 'Fuente de poder',
        'case' => 'Gabinete',
        'cooler' => 'Enfriamiento',
    ],

    'fields' => [
        'cpu' => [
            'platform' => ['label' => 'Plataforma', 'type' => 'select', 'options' => ['AMD' => 'AMD', 'Intel' => 'Intel']],
            'socket' => ['label' => 'Socket', 'type' => 'select', 'options' => 'dynamic:socket'],
            'tdp_w' => ['label' => 'TDP (watts)', 'type' => 'number'],
            'graficos_integrados' => ['label' => '¿Tiene gráficos integrados?', 'type' => 'select', 'options' => ['no' => 'No', 'si' => 'Sí']],
            'incluye_cooler' => ['label' => '¿Incluye cooler de stock?', 'type' => 'select', 'options' => ['no' => 'No', 'si' => 'Sí']],
        ],

        'motherboard' => [
            'socket' => ['label' => 'Socket', 'type' => 'select', 'options' => 'dynamic:socket'],
            'ram_type' => ['label' => 'Tipo de RAM', 'type' => 'select', 'options' => 'dynamic:ram_type'],
            'form_factor' => ['label' => 'Form factor', 'type' => 'select', 'options' => 'dynamic:form_factor'],
            'max_ram_gb' => ['label' => 'RAM máxima soportada (GB)', 'type' => 'number'],
        ],

        'ram' => [
            'type' => ['label' => 'Tipo', 'type' => 'select', 'options' => 'dynamic:ram_type'],
        ],

        'storage' => [
            'tipo' => ['label' => 'Tipo', 'type' => 'select', 'options' => 'dynamic:storage_type'],
            'capacity_gb' => ['label' => 'Capacidad (GB)', 'type' => 'number'],
        ],

        'case' => [
            'form_factors' => ['label' => 'Placas que entran en el gabinete', 'type' => 'checkboxes', 'options' => 'dynamic:form_factor'],
            'max_gpu_length_mm' => ['label' => 'Largo máximo de tarjeta gráfica (mm)', 'type' => 'number'],
            'max_radiator_mm' => ['label' => 'Radiador líquido máximo soportado', 'type' => 'select', 'options' => 'dynamic:radiator_size'],
        ],

        'gpu' => [
            'length_mm' => ['label' => 'Largo de la tarjeta (mm)', 'type' => 'number'],
            'power_draw_w' => ['label' => 'Consumo estimado (watts)', 'type' => 'number'],
            // Clasificación manual (no un número de FPS inventado) — el
            // admin la carga a criterio propio para cada tarjeta, y el
            // asistente público solo muestra la que ya cargaste.
            'tier' => ['label' => 'Rendimiento aproximado', 'type' => 'select', 'options' => 'dynamic:gpu_tier'],
        ],

        'cooler' => [
            'sockets' => ['label' => 'Sockets compatibles', 'type' => 'checkboxes', 'options' => 'dynamic:socket'],
            'radiator_mm' => ['label' => 'Radiador (0 = enfriamiento por aire)', 'type' => 'select', 'options' => 'dynamic:radiator_size'],
        ],

        'psu' => [
            'watts' => ['label' => 'Watts', 'type' => 'number'],
            'certificacion' => ['label' => 'Certificación 80 Plus', 'type' => 'select', 'options' => 'dynamic:psu_certification'],
        ],
    ],

];
