<?php

// Fuente única de verdad para "Arma tu PC": qué tipos de componente existen
// y qué campos de compatibilidad tiene cada uno. La usan el formulario de
// productos del admin (para saber qué inputs mostrar según la categoría) y,
// más adelante, el motor de compatibilidad del armador público.

$sockets = [
    'AM4' => 'AM4',
    'AM5' => 'AM5',
    'LGA1700' => 'LGA1700',
    'LGA1200' => 'LGA1200',
];

$radiatorSizes = [
    '0' => 'No soporta / no aplica',
    '120' => '120mm',
    '240' => '240mm',
    '280' => '280mm',
    '360' => '360mm',
];

return [

    'component_types' => [
        'cpu' => 'Procesador',
        'motherboard' => 'Placa madre',
        'ram' => 'Memoria RAM',
        'case' => 'Gabinete',
        'gpu' => 'Tarjeta gráfica',
        'cooler' => 'Enfriamiento',
        'psu' => 'Fuente de poder',
    ],

    'fields' => [
        'cpu' => [
            'platform' => ['label' => 'Plataforma', 'type' => 'select', 'options' => ['AMD' => 'AMD', 'Intel' => 'Intel']],
            'socket' => ['label' => 'Socket', 'type' => 'select', 'options' => $sockets],
            'tdp_w' => ['label' => 'TDP (watts)', 'type' => 'number'],
        ],

        'motherboard' => [
            'socket' => ['label' => 'Socket', 'type' => 'select', 'options' => $sockets],
            'ram_type' => ['label' => 'Tipo de RAM', 'type' => 'select', 'options' => ['DDR4' => 'DDR4', 'DDR5' => 'DDR5']],
            'form_factor' => ['label' => 'Form factor', 'type' => 'select', 'options' => ['ATX' => 'ATX', 'mATX' => 'mATX', 'ITX' => 'ITX']],
            'max_ram_gb' => ['label' => 'RAM máxima soportada (GB)', 'type' => 'number'],
        ],

        'ram' => [
            'type' => ['label' => 'Tipo', 'type' => 'select', 'options' => ['DDR4' => 'DDR4', 'DDR5' => 'DDR5']],
        ],

        'case' => [
            'form_factors' => ['label' => 'Placas que entran en el gabinete', 'type' => 'checkboxes', 'options' => ['ATX' => 'ATX', 'mATX' => 'mATX', 'ITX' => 'ITX']],
            'max_gpu_length_mm' => ['label' => 'Largo máximo de tarjeta gráfica (mm)', 'type' => 'number'],
            'max_radiator_mm' => ['label' => 'Radiador líquido máximo soportado', 'type' => 'select', 'options' => $radiatorSizes],
        ],

        'gpu' => [
            'length_mm' => ['label' => 'Largo de la tarjeta (mm)', 'type' => 'number'],
            'power_draw_w' => ['label' => 'Consumo estimado (watts)', 'type' => 'number'],
        ],

        'cooler' => [
            'sockets' => ['label' => 'Sockets compatibles', 'type' => 'checkboxes', 'options' => $sockets],
            'radiator_mm' => ['label' => 'Radiador (0 = enfriamiento por aire)', 'type' => 'select', 'options' => $radiatorSizes],
        ],

        'psu' => [
            'watts' => ['label' => 'Watts', 'type' => 'number'],
        ],
    ],

];
