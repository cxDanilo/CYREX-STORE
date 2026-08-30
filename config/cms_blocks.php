<?php

/*
|--------------------------------------------------------------------------
| Registro de tipos de bloque del CMS
|--------------------------------------------------------------------------
|
| Cada tipo de bloque define:
| - view: la plantilla Blade que lo renderiza (usada por PageRenderer,
|   tanto para el sitio público como para el preview del editor).
| - category: agrupa el bloque en la paleta del editor.
| - icon: SVG inline mostrado en la paleta del editor.
| - defaults: valores por defecto de sus campos (se fusionan con `data`
|   al renderizar, para que un bloque guardado antes de un cambio de
|   schema no rompa la vista si le falta una clave nueva).
| - fields: metadata de edición (tipo de campo, label, opciones).
|   PageRenderer NUNCA lee esta clave — es exclusivamente para que un
|   editor (hoy GrapesJS, mañana el que sea) sepa qué formulario
|   construir. Un editor nuevo no requiere tocar PageRenderer.
|
| Tipos de campo soportados por el editor: text, textarea, number,
| select (requiere 'options'; 'categories' como options se resuelve
| dinámicamente en Admin\PageBlockController con categorías reales),
| repeater (requiere 'fields' anidado, mismo vocabulario de tipos).
|
| Para agregar un tipo de bloque nuevo: crear su vista en
| resources/views/cms/blocks/ y agregar una entrada acá. Nada más —
| ni PageRenderer ni el editor necesitan tocarse.
|
*/

return [

    'hero_simple' => [
        'label' => 'Hero',
        'view' => 'cms.blocks.hero-simple',
        'category' => 'Contenido',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 10h10M7 14h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['eyebrow' => '', 'titulo' => '', 'titulo_destacado' => '', 'subtitulo' => '', 'cta_label' => '', 'cta_url' => '', 'cta2_label' => '', 'cta2_url' => '', 'personaje_url' => '', 'personaje_size' => '', 'tamano' => 'estandar'],
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Texto pequeño superior (opcional)'],
            'titulo' => ['type' => 'textarea', 'label' => 'Título'],
            'titulo_destacado' => ['type' => 'text', 'label' => 'Palabras finales destacadas en dorado (opcional)'],
            'subtitulo' => ['type' => 'textarea', 'label' => 'Subtítulo'],
            'cta_label' => ['type' => 'text', 'label' => 'Texto del botón 1 (opcional)'],
            'cta_url' => ['type' => 'text', 'label' => 'Link del botón 1'],
            'cta2_label' => ['type' => 'text', 'label' => 'Texto del botón 2 (opcional)'],
            'cta2_url' => ['type' => 'text', 'label' => 'Link del botón 2'],
            'personaje_url' => ['type' => 'media', 'label' => 'Ilustración/personaje a la derecha (opcional, fondo transparente recomendado)'],
            'personaje_size' => ['type' => 'number', 'label' => 'Tamaño de la ilustración en píxeles (opcional — vacío = automático)'],
            'tamano' => ['type' => 'select', 'label' => 'Tamaño', 'options' => [
                ['id' => 'estandar', 'name' => 'Estándar'],
                ['id' => 'grande', 'name' => 'Grande (portada)'],
            ]],
        ],
    ],

    'hero_video' => [
        'label' => 'Hero con video',
        'view' => 'cms.blocks.hero-video',
        'category' => 'Contenido',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M10 9l5 3-5 3V9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
        'defaults' => [
            'video_url' => '',
            'poster_url' => '',
            'poster_url_mobile' => '',
            'titulo' => '',
            'boton1_texto' => '', 'boton1_url' => '',
            'boton2_texto' => '', 'boton2_url' => '',
        ],
        'fields' => [
            'video_url' => ['type' => 'text', 'label' => 'Video: link de YouTube/Vimeo o URL de un archivo .mp4'],
            'poster_url' => ['type' => 'media', 'label' => 'Imagen de respaldo — escritorio (se usa mientras carga el video, y en celular si no hay una específica abajo)'],
            'poster_url_mobile' => ['type' => 'media', 'label' => 'Imagen de respaldo — celular (opcional, para que sea una foto distinta a la de escritorio)'],
            'titulo' => ['type' => 'textarea', 'label' => 'Título'],
            'boton1_texto' => ['type' => 'text', 'label' => 'Botón 1 — texto'],
            'boton1_url' => ['type' => 'text', 'label' => 'Botón 1 — link'],
            'boton2_texto' => ['type' => 'text', 'label' => 'Botón 2 — texto'],
            'boton2_url' => ['type' => 'text', 'label' => 'Botón 2 — link'],
        ],
    ],

    'titulo' => [
        'label' => 'Título',
        'view' => 'cms.blocks.titulo',
        'category' => 'Contenido',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6 5v14M18 5v14M6 12h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['texto' => '', 'tamano' => 'grande'],
        'fields' => [
            'texto' => ['type' => 'text', 'label' => 'Texto'],
            'tamano' => ['type' => 'select', 'label' => 'Tamaño', 'options' => [
                ['id' => 'grande', 'name' => 'Grande'],
                ['id' => 'mediano', 'name' => 'Mediano'],
                ['id' => 'chico', 'name' => 'Chico'],
            ]],
        ],
    ],

    'texto_libre' => [
        'label' => 'Texto',
        'view' => 'cms.blocks.texto-libre',
        'category' => 'Contenido',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 11h16M4 16h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['texto' => ''],
        'fields' => [
            'texto' => ['type' => 'textarea', 'label' => 'Texto'],
        ],
    ],

    'boton' => [
        'label' => 'Botón',
        'view' => 'cms.blocks.boton',
        'category' => 'Contenido',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="9" width="18" height="6" rx="3" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => ['texto' => '', 'url' => '', 'estilo' => 'primario'],
        'fields' => [
            'texto' => ['type' => 'text', 'label' => 'Texto del botón'],
            'url' => ['type' => 'text', 'label' => 'Link'],
            'estilo' => ['type' => 'select', 'label' => 'Estilo', 'options' => [
                ['id' => 'primario', 'name' => 'Dorado (primario)'],
                ['id' => 'secundario', 'name' => 'Contorno (secundario)'],
            ]],
        ],
    ],

    'cta' => [
        'label' => 'CTA',
        'view' => 'cms.blocks.cta',
        'category' => 'Contenido',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M9 12h6M12 9l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'defaults' => ['texto' => '', 'boton_label' => '', 'boton_url' => ''],
        'fields' => [
            'texto' => ['type' => 'textarea', 'label' => 'Texto'],
            'boton_label' => ['type' => 'text', 'label' => 'Texto del botón'],
            'boton_url' => ['type' => 'text', 'label' => 'Link del botón'],
        ],
    ],

    'separador' => [
        'label' => 'Separador',
        'view' => 'cms.blocks.separador',
        'category' => 'Contenido',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 12h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['tamano' => 'mediano'],
        'fields' => [
            'tamano' => ['type' => 'select', 'label' => 'Espaciado', 'options' => [
                ['id' => 'chico', 'name' => 'Chico'],
                ['id' => 'mediano', 'name' => 'Mediano'],
                ['id' => 'grande', 'name' => 'Grande'],
            ]],
        ],
    ],

    'imagen' => [
        'label' => 'Imagen',
        'view' => 'cms.blocks.imagen',
        'category' => 'Medios',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/><circle cx="8.5" cy="9.5" r="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M21 16l-5.5-5.5L6 20" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
        'defaults' => ['url' => '', 'alt' => '', 'leyenda' => ''],
        'fields' => [
            'url' => ['type' => 'media', 'label' => 'Imagen'],
            'alt' => ['type' => 'text', 'label' => 'Texto alternativo'],
            'leyenda' => ['type' => 'text', 'label' => 'Leyenda (opcional)'],
        ],
    ],

    'galeria' => [
        'label' => 'Galería',
        'view' => 'cms.blocks.galeria',
        'category' => 'Medios',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => ['items' => []],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Imágenes', 'fields' => [
                'url' => ['type' => 'media', 'label' => 'Imagen'],
                'alt' => ['type' => 'text', 'label' => 'Texto alternativo'],
            ]],
        ],
    ],

    'video' => [
        'label' => 'Video',
        'view' => 'cms.blocks.video',
        'category' => 'Medios',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M10 9l5 3-5 3V9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
        'defaults' => ['titulo' => '', 'url' => ''],
        'fields' => [
            'titulo' => ['type' => 'text', 'label' => 'Título (opcional)'],
            'url' => ['type' => 'text', 'label' => 'URL de YouTube o Vimeo'],
        ],
    ],

    'banner' => [
        'label' => 'Banner',
        'view' => 'cms.blocks.banner',
        'category' => 'Medios',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M6 15h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['imagen_url' => '', 'titulo' => '', 'cta_label' => '', 'cta_url' => ''],
        'fields' => [
            'imagen_url' => ['type' => 'media', 'label' => 'Imagen de fondo'],
            'titulo' => ['type' => 'text', 'label' => 'Título'],
            'cta_label' => ['type' => 'text', 'label' => 'Texto del botón'],
            'cta_url' => ['type' => 'text', 'label' => 'Link del botón'],
        ],
    ],

    'carrusel' => [
        'label' => 'Carrusel',
        'view' => 'cms.blocks.carrusel',
        'category' => 'Medios',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="5" y="5" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M2 12h1.5M20.5 12H22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['items' => []],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Slides', 'fields' => [
                'url' => ['type' => 'media', 'label' => 'Imagen'],
                'texto' => ['type' => 'text', 'label' => 'Leyenda (opcional)'],
            ]],
        ],
    ],

    'marcas' => [
        'label' => 'Marcas',
        'view' => 'cms.blocks.marcas',
        'category' => 'Medios',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="14" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="18" cy="16" r="3" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => ['items' => [], 'logo_size' => 64],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Logos', 'fields' => [
                'url' => ['type' => 'media', 'label' => 'Logo', 'trim' => true],
                'nombre' => ['type' => 'text', 'label' => 'Nombre de la marca'],
            ]],
            'logo_size' => ['type' => 'range', 'label' => 'Tamaño de los logos', 'min' => 32, 'max' => 120, 'step' => 4, 'unit' => 'px'],
        ],
    ],

    'marcas_mosaico' => [
        'label' => 'Marcas (mosaico)',
        'view' => 'cms.blocks.marcas-mosaico',
        'category' => 'Medios',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="10" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="15" y="3" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="15" y="11" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="15" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="11" y="15" width="10" height="6" rx="1.5" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => ['titulo' => 'Explorá nuestras marcas', 'items' => [], 'intervalo' => 6],
        'fields' => [
            'titulo' => ['type' => 'text', 'label' => 'Título de la sección'],
            'items' => ['type' => 'repeater', 'label' => 'Marcas', 'fields' => [
                'imagen' => ['type' => 'media', 'label' => 'Imagen'],
                'nombre' => ['type' => 'text', 'label' => 'Nombre (opcional, se muestra sobre la imagen)'],
                'link' => ['type' => 'text', 'label' => 'Link al hacer click — ej: busca la marca en la tienda y pega esa URL, como /tienda?q=asus'],
            ]],
            'intervalo' => ['type' => 'number', 'label' => 'Segundos entre cada cambio automático (0 = desactivado)'],
        ],
    ],

    'sucursales' => [
        'label' => 'Sucursales',
        'view' => 'cms.blocks.sucursales',
        'category' => 'Comercio',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="9.5" r="2.2" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => ['items' => []],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Sucursales', 'fields' => [
                'nombre' => ['type' => 'text', 'label' => 'Nombre (ej. "Sucursal Central")'],
                'ciudad' => ['type' => 'text', 'label' => 'Ciudad'],
                'direccion' => ['type' => 'text', 'label' => 'Dirección o texto de búsqueda (para el link "Abrir en Maps" — opcional)'],
                'mapa_embed' => ['type' => 'textarea', 'label' => 'Mapa: pega aquí el código "Insertar un mapa" de Google Maps (Compartir → Insertar un mapa → Copiar HTML) — opcional, si no lo pones se usa la Dirección de arriba'],
                'proximamente' => ['type' => 'select', 'label' => '¿Todavía no está abierta?', 'options' => [
                    ['id' => '', 'name' => 'No — ya está operando'],
                    ['id' => 'si', 'name' => 'Sí — mostrar como "próximamente"'],
                ]],
                'mensaje_proximamente' => ['type' => 'text', 'label' => 'Mensaje de "próximamente" (opcional)'],
                'asesores' => ['type' => 'repeater', 'label' => 'Asesores de esta sucursal', 'fields' => [
                    'nombre' => ['type' => 'text', 'label' => 'Nombre'],
                    'cargo' => ['type' => 'text', 'label' => 'Cargo / etiqueta (ej. "Asesor Antezana")'],
                    'whatsapp' => ['type' => 'text', 'label' => 'WhatsApp (con código de país, ej. 59177947379 — si lo dejas vacío usa el WhatsApp general de la tienda)'],
                ]],
            ]],
        ],
    ],

    'mapa' => [
        'label' => 'Mapa',
        'view' => 'cms.blocks.mapa',
        'category' => 'Medios',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="9.5" r="2.2" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => ['direccion' => ''],
        'fields' => [
            'direccion' => ['type' => 'text', 'label' => 'Dirección o lugar'],
        ],
    ],

    'productos' => [
        'label' => 'Productos',
        'view' => 'cms.blocks.productos',
        'category' => 'Comercio',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M6 8h12l-1 12H7L6 8z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9 8V6a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => ['eyebrow' => '', 'titulo' => '', 'titulo_destacado' => '', 'subtitulo' => '', 'categoria' => '', 'limite' => 4, 'orden' => 'recientes'],
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Texto pequeño superior (opcional)'],
            'titulo' => ['type' => 'text', 'label' => 'Título (opcional)'],
            'titulo_destacado' => ['type' => 'text', 'label' => 'Palabras finales destacadas en dorado (opcional)'],
            'subtitulo' => ['type' => 'textarea', 'label' => 'Subtítulo (opcional)'],
            'categoria' => ['type' => 'select', 'label' => 'Categoría', 'options' => 'categories'],
            'limite' => ['type' => 'number', 'label' => 'Cantidad de productos'],
            'orden' => ['type' => 'select', 'label' => 'Orden', 'options' => [
                ['id' => 'recientes', 'name' => 'Más recientes'],
                ['id' => 'aleatorio_diario', 'name' => 'Aleatorio (cambia cada día)'],
            ]],
        ],
    ],

    'banner_productos' => [
        'label' => 'Banner con productos',
        'view' => 'cms.blocks.banner-productos',
        'category' => 'Comercio',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 14h4M7 17h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><rect x="14" y="12" width="5" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => [
            'imagen_fondo' => '',
            'eyebrow' => '',
            'titulo' => '',
            'subtitulo' => '',
            'boton_texto' => '', 'boton_url' => '',
            'modo' => 'categoria',
            'categoria' => '',
            'limite' => 3,
            'items' => [],
            'card_opacidad' => 55,
        ],
        'fields' => [
            'imagen_fondo' => ['type' => 'media', 'label' => 'Imagen de fondo'],
            'eyebrow' => ['type' => 'text', 'label' => 'Texto pequeño superior (opcional)'],
            'titulo' => ['type' => 'textarea', 'label' => 'Título'],
            'subtitulo' => ['type' => 'textarea', 'label' => 'Subtítulo (opcional)'],
            'boton_texto' => ['type' => 'text', 'label' => 'Texto del botón (opcional)'],
            'boton_url' => ['type' => 'text', 'label' => 'Link del botón'],
            'card_opacidad' => ['type' => 'range', 'label' => 'Opacidad de las tarjetas de producto', 'min' => 0, 'max' => 100, 'step' => 5, 'unit' => '%'],
            'modo' => ['type' => 'select', 'label' => 'Cómo elegir los productos', 'options' => [
                ['id' => 'categoria', 'name' => 'Automático por categoría'],
                ['id' => 'manual', 'name' => 'Elegir productos puntuales'],
            ]],
            'categoria' => ['type' => 'select', 'label' => 'Categoría (si el modo es "automático")', 'options' => 'categories'],
            'limite' => ['type' => 'number', 'label' => 'Cantidad de productos (si el modo es "automático")'],
            'items' => ['type' => 'repeater', 'label' => 'Productos puntuales (si el modo es "elegir productos")', 'fields' => [
                'producto' => ['type' => 'select', 'label' => 'Producto', 'options' => 'products'],
            ]],
        ],
    ],

    'categoria_rotativa' => [
        'label' => 'Categoría del día',
        'view' => 'cms.blocks.categoria-rotativa',
        'category' => 'Comercio',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 4h7v7H4V4zM13 4h7v7h-7V4zM4 13h7v7H4v-7zM13 13h7v7h-7v-7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
        'defaults' => ['etiqueta' => 'Novedades', 'posicion' => 1, 'limite' => 4],
        'fields' => [
            'etiqueta' => ['type' => 'text', 'label' => 'Insignia (ej. "Novedades")'],
            'posicion' => ['type' => 'number', 'label' => 'Posición en la rotación diaria (1, 2, 3... — usa un número distinto en cada bloque de este tipo en la misma página para que no se repita la categoría)'],
            'limite' => ['type' => 'number', 'label' => 'Cantidad de productos'],
        ],
    ],

    'categorias_destacadas' => [
        'label' => 'Categorías destacadas',
        'view' => 'cms.blocks.categorias-destacadas',
        'category' => 'Comercio',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="2" y="9" width="7" height="6" rx="3" stroke="currentColor" stroke-width="1.5"/><rect x="10" y="9" width="7" height="6" rx="3" stroke="currentColor" stroke-width="1.5"/><rect x="18" y="9" width="4" height="6" rx="2" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => [],
        'fields' => [],
    ],

    'cta_whatsapp' => [
        'label' => 'CTA de WhatsApp',
        'view' => 'cms.blocks.cta-whatsapp',
        'category' => 'Comercio',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M9 10c0 3 2 5 5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['texto' => 'Escríbenos por WhatsApp'],
        'fields' => [
            'texto' => ['type' => 'text', 'label' => 'Texto del botón'],
        ],
    ],

    'formulario' => [
        'label' => 'Formulario',
        'view' => 'cms.blocks.formulario',
        'category' => 'Comercio',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 9h10M7 13h10M7 17h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['titulo' => '', 'boton_texto' => 'Enviar por WhatsApp'],
        'fields' => [
            'titulo' => ['type' => 'text', 'label' => 'Título (opcional)'],
            'boton_texto' => ['type' => 'text', 'label' => 'Texto del botón'],
        ],
    ],

    'faq' => [
        'label' => 'FAQ',
        'view' => 'cms.blocks.faq',
        'category' => 'Prueba social',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M10 9a2 2 0 1 1 3 1.7c-.7.5-1 .8-1 1.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="12" cy="16.3" r=".2" stroke="currentColor" stroke-width="1.8"/></svg>',
        'defaults' => ['items' => []],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Preguntas', 'fields' => [
                'pregunta' => ['type' => 'text', 'label' => 'Pregunta'],
                'respuesta' => ['type' => 'textarea', 'label' => 'Respuesta'],
            ]],
        ],
    ],

    'cards' => [
        'label' => 'Cards',
        'view' => 'cms.blocks.cards',
        'category' => 'Prueba social',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="6" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="9" y="4" width="6" height="16" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="16" y="7" width="6" height="13" rx="1.5" stroke="currentColor" stroke-width="1.5"/></svg>',
        'defaults' => ['items' => []],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Cards', 'fields' => [
                'titulo' => ['type' => 'text', 'label' => 'Título'],
                'texto' => ['type' => 'textarea', 'label' => 'Texto'],
            ]],
        ],
    ],

    'testimonios' => [
        'label' => 'Testimonios',
        'view' => 'cms.blocks.testimonios',
        'category' => 'Prueba social',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M7 8c-2 0-3 1.5-3 3.5S5 15 7 15v3l-3-2" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M16 8c-2 0-3 1.5-3 3.5s1 3.5 3 3.5v3l-3-2" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
        'defaults' => ['items' => []],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Testimonios', 'fields' => [
                'nombre' => ['type' => 'text', 'label' => 'Nombre'],
                'texto' => ['type' => 'textarea', 'label' => 'Testimonio'],
            ]],
        ],
    ],

    'garantias' => [
        'label' => 'Garantías',
        'view' => 'cms.blocks.garantias',
        'category' => 'Prueba social',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
        'defaults' => ['items' => []],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Ítems', 'fields' => [
                'icono' => ['type' => 'select', 'label' => 'Ícono', 'options' => [
                    ['id' => 'check', 'name' => 'Check'],
                    ['id' => 'shield', 'name' => 'Garantía'],
                    ['id' => 'truck', 'name' => 'Envío'],
                    ['id' => 'support', 'name' => 'Soporte'],
                ]],
                'texto' => ['type' => 'textarea', 'label' => 'Texto', 'maxlength' => 160],
            ]],
        ],
    ],

    'numeros_destacados' => [
        'label' => 'Números destacados',
        'view' => 'cms.blocks.numeros-destacados',
        'category' => 'Prueba social',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M5 19V11M12 19V5M19 19v-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'defaults' => ['items' => []],
        'fields' => [
            'items' => ['type' => 'repeater', 'label' => 'Números', 'fields' => [
                'numero' => ['type' => 'text', 'label' => 'Número'],
                'etiqueta' => ['type' => 'text', 'label' => 'Etiqueta'],
            ]],
        ],
    ],

    'redes_sociales' => [
        'label' => 'Publicaciones de redes',
        'view' => 'cms.blocks.redes-sociales',
        'category' => 'Prueba social',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor"/></svg>',
        'defaults' => ['titulo' => '', 'items' => []],
        'fields' => [
            'titulo' => ['type' => 'text', 'label' => 'Título (opcional)'],
            'items' => ['type' => 'repeater', 'label' => 'Publicaciones', 'fields' => [
                'plataforma' => ['type' => 'select', 'label' => 'Red social', 'options' => [
                    ['id' => 'instagram', 'name' => 'Instagram'],
                    ['id' => 'tiktok', 'name' => 'TikTok'],
                    ['id' => 'facebook', 'name' => 'Facebook'],
                ]],
                'imagen' => ['type' => 'media', 'label' => 'Captura de la publicación'],
                'texto' => ['type' => 'text', 'label' => 'Texto corto (opcional)'],
                'url' => ['type' => 'text', 'label' => 'Link a la publicación real'],
            ]],
        ],
    ],

    'html_libre' => [
        'label' => 'HTML',
        'view' => 'cms.blocks.html-libre',
        'category' => 'Avanzado',
        'icon' => '<svg viewBox="0 0 24 24" fill="none"><path d="M8 6l-5 6 5 6M16 6l5 6-5 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'defaults' => ['html' => ''],
        'fields' => [
            'html' => ['type' => 'textarea', 'label' => 'HTML crudo (sin sanitizar — usar con cuidado)'],
        ],
    ],

];
