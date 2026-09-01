{{-- Set de íconos del menú del admin — trazos simples (rect/circle/path
     con líneas rectas o arcos básicos), pensado para dibujarse bien a
     mano sin depender de una librería de íconos externa. Uno por
     nombre, mismo viewBox 20x20 para que midan todos igual. --}}
@php
  $paths = [
    'dashboard' => '<rect x="2.5" y="2.5" width="6" height="6" rx="1.3"/><rect x="11.5" y="2.5" width="6" height="6" rx="1.3"/><rect x="2.5" y="11.5" width="6" height="6" rx="1.3"/><rect x="11.5" y="11.5" width="6" height="6" rx="1.3"/>',
    'analitica' => '<path d="M3 16.5V10"/><path d="M9.3 16.5V4"/><path d="M15.5 16.5v-6.5"/>',
    'productos' => '<path d="M3 6.5 10 3l7 3.5v7L10 17 3 13.5v-7Z"/><path d="M3 6.5 10 10l7-3.5"/><path d="M10 10v7"/>',
    'categorias' => '<path d="M3 5.5c0-.83.67-1.5 1.5-1.5h3.6c.5 0 .96.24 1.25.65l.8 1.1c.29.4.75.65 1.25.65h4.35c.83 0 1.5.67 1.5 1.5v6.6c0 .83-.67 1.5-1.5 1.5h-11c-.83 0-1.5-.67-1.5-1.5v-8.9Z"/>',
    'promociones' => '<path d="M10.5 3h4.5a2 2 0 0 1 2 2v4.5a2 2 0 0 1-.59 1.41l-7 7a2 2 0 0 1-2.82 0l-4.5-4.5a2 2 0 0 1 0-2.82l7-7A2 2 0 0 1 10.5 3Z"/><circle cx="13" cy="7" r="1.1"/>',
    'combos' => '<rect x="2.5" y="6.5" width="7" height="7" rx="1.2"/><rect x="10.5" y="6.5" width="7" height="7" rx="1.2"/><path d="M6 13.5V16M14 13.5V16"/>',
    'ofertas' => '<path d="M3 10.5V4.5a1 1 0 0 1 1-1h6l7 7-7 7-7-7Z"/><circle cx="7.3" cy="7.3" r="1.3"/>',
    'importar' => '<path d="M10 13V3"/><path d="m6.5 6.5 3.5-3.5 3.5 3.5"/><path d="M3.5 13v2.5a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V13"/>',
    'atributos' => '<path d="M3 5.5h9"/><path d="M15.5 5.5h1.5"/><circle cx="12.5" cy="5.5" r="1.7"/><path d="M3 10h1.5"/><path d="M8 10h9"/><circle cx="5.5" cy="10" r="1.7"/><path d="M3 14.5h9"/><path d="M15.5 14.5h1.5"/><circle cx="12.5" cy="14.5" r="1.7"/>',
    'paginas' => '<path d="M6 2.5h5.5L15 6v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-13.5a1 1 0 0 1 1-1Z"/><path d="M11.5 2.5V6H15"/><path d="M7.5 10h5"/><path d="M7.5 13h5"/>',
    'plantillas' => '<rect x="2.5" y="3" width="15" height="14" rx="1.3"/><path d="M2.5 7.5h15"/><path d="M8 7.5V17"/>',
    'medios' => '<rect x="2.5" y="4" width="15" height="12" rx="1.3"/><circle cx="7" cy="8.5" r="1.4"/><path d="m4 15 4-4 3 3 3.5-4.5 3 3"/>',
    'menus' => '<path d="M3 5.5h14"/><path d="M3 10h14"/><path d="M3 14.5h14"/>',
    'redes' => '<circle cx="5" cy="10" r="2"/><circle cx="15" cy="5" r="2"/><circle cx="15" cy="15" r="2"/><path d="m6.8 9 6.4-3"/><path d="m6.8 11 6.4 3"/>',
    'historial' => '<circle cx="10" cy="10" r="7"/><path d="M10 6v4l3 2"/>',
    'versiones' => '<path d="M4 10a6 6 0 1 0 1.8-4.3"/><path d="M2.5 3.5v3.5H6"/><path d="M10 7v3l2 1.5"/>',
    'usuarios' => '<circle cx="10" cy="6.5" r="3"/><path d="M4 17c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/>',
    'ajustes' => '<circle cx="10" cy="10" r="2.6"/><path d="M10 3v2M10 15v2M17 10h-2M5 10H3M14.8 5.2l-1.4 1.4M6.6 13.4l-1.4 1.4M14.8 14.8l-1.4-1.4M6.6 6.6 5.2 5.2"/>',
    'ver-sitio' => '<path d="M8.5 4H4.5a1.5 1.5 0 0 0-1.5 1.5v10A1.5 1.5 0 0 0 4.5 17h10a1.5 1.5 0 0 0 1.5-1.5v-4"/><path d="M11.5 3H17v5.5"/><path d="M9 11 17 3"/>',
    'purgar' => '<path d="M16 10a6 6 0 1 1-1.8-4.3"/><path d="M16 3v3.5h-3.5"/>',
    'salir' => '<path d="M8 3H4.5a1.5 1.5 0 0 0-1.5 1.5v11A1.5 1.5 0 0 0 4.5 17H8"/><path d="M13 6.5 17 10l-4 3.5"/><path d="M17 10H8"/>',
    'cerrar' => '<path d="m5 5 10 10"/><path d="m15 5-10 10"/>',
    'chevron' => '<path d="m6 8 4 4 4-4"/>',
  ];
@endphp
<svg class="admin-nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
