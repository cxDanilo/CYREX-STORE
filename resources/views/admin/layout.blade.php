<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') — Cyrex Store</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v={{ filemtime(public_path('css/fonts.css')) }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="{{ asset('js/admin-autoslug.js') }}?v={{ filemtime(public_path('js/admin-autoslug.js')) }}"></script>
@yield('styles')
</head>
<body>

<div class="admin-body" x-data="{ navOpen: false }" x-on:keydown.escape.window="navOpen = false" x-effect="document.body.style.overflow = navOpen ? 'hidden' : ''">
  <div class="admin-nav-backdrop" x-show="navOpen" x-cloak x-on:click="navOpen = false" x-transition.opacity></div>

  <aside class="admin-sidebar" :class="{ 'is-open': navOpen }">
    <div class="admin-logo-row">
      <div class="admin-logo"><a href="{{ route('admin.dashboard') }}" style="color:inherit;">CYREX<span>.</span> ADMIN</a></div>
      <button type="button" class="admin-nav-close" aria-label="Cerrar menú" x-on:click="navOpen = false">
        @include('partials.admin-icon', ['name' => 'cerrar'])
      </button>
    </div>
    @php
      $navGroups = [
        'catalogo' => [
          'label' => 'Catálogo',
          'match' => ['admin.productos.*', 'admin.categorias.*', 'admin.promociones.*', 'admin.combos.*', 'admin.woocommerce.*', 'admin.attribute-fields.*', 'admin.pc-builder-options.*'],
        ],
        'contenido' => [
          'label' => 'Contenido',
          'match' => ['admin.paginas.*', 'admin.plantillas.*', 'admin.medios.*', 'admin.menus.*', 'admin.redes.*'],
        ],
        'actividad' => [
          'label' => 'Actividad',
          'match' => ['admin.historial.*', 'admin.changelog.*'],
        ],
        'cuenta' => [
          'label' => 'Cuenta',
          'match' => ['admin.usuarios.*', 'admin.settings.*'],
        ],
      ];
      $activeGroup = collect($navGroups)->first(fn ($g) => request()->routeIs($g['match']));
      $activeGroupKey = $activeGroup ? array_search($activeGroup, $navGroups, true) : null;
    @endphp
    <nav class="admin-nav" x-data="{ openGroup: @js($activeGroupKey) }" x-on:click="$event.target.closest('a') && (navOpen = false)">
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'dashboard']) Dashboard</a>

      {{-- Catálogo --}}
      <button type="button" class="admin-nav-group-toggle" :class="{ 'is-open': openGroup === 'catalogo' }" @click="openGroup = openGroup === 'catalogo' ? null : 'catalogo'">
        Catálogo
        @include('partials.admin-icon', ['name' => 'chevron'])
      </button>
      <div class="admin-nav-group" x-show="openGroup === 'catalogo'" x-collapse x-cloak>
        <a href="{{ route('admin.productos.index') }}" class="{{ request()->routeIs('admin.productos.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'productos']) Productos</a>
        <a href="{{ route('admin.categorias.index') }}" class="{{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'categorias']) Categorías</a>
        <a href="{{ route('admin.promociones.index') }}" class="{{ request()->routeIs('admin.promociones.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'promociones']) Promociones</a>
        <a href="{{ route('admin.combos.index') }}" class="{{ request()->routeIs('admin.combos.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'combos']) Combos</a>
        @if(auth()->user()->isAdmin())
          <a href="{{ route('admin.woocommerce.create') }}" class="{{ request()->routeIs('admin.woocommerce.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'importar']) Importar WooCommerce</a>
        @endif
        <a href="{{ route('admin.attribute-fields.index') }}" class="{{ request()->routeIs('admin.attribute-fields.*') || request()->routeIs('admin.pc-builder-options.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'atributos']) Compatibilidad y atributos</a>
      </div>

      {{-- Contenido --}}
      <button type="button" class="admin-nav-group-toggle" :class="{ 'is-open': openGroup === 'contenido' }" @click="openGroup = openGroup === 'contenido' ? null : 'contenido'">
        Contenido
        @include('partials.admin-icon', ['name' => 'chevron'])
      </button>
      <div class="admin-nav-group" x-show="openGroup === 'contenido'" x-collapse x-cloak>
        <a href="{{ route('admin.paginas.index') }}" class="{{ request()->routeIs('admin.paginas.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'paginas']) Páginas</a>
        <a href="{{ route('admin.plantillas.index') }}" class="{{ request()->routeIs('admin.plantillas.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'plantillas']) Plantillas</a>
        <a href="{{ route('admin.medios.index') }}" class="{{ request()->routeIs('admin.medios.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'medios']) Medios</a>
        <a href="{{ route('admin.menus.index') }}" class="{{ request()->routeIs('admin.menus.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'menus']) Menús</a>
        <a href="{{ route('admin.redes.index') }}" class="{{ request()->routeIs('admin.redes.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'redes']) Redes sociales</a>
      </div>

      {{-- Actividad --}}
      <button type="button" class="admin-nav-group-toggle" :class="{ 'is-open': openGroup === 'actividad' }" @click="openGroup = openGroup === 'actividad' ? null : 'actividad'">
        Actividad
        @include('partials.admin-icon', ['name' => 'chevron'])
      </button>
      <div class="admin-nav-group" x-show="openGroup === 'actividad'" x-collapse x-cloak>
        <a href="{{ route('admin.historial.index') }}" class="{{ request()->routeIs('admin.historial.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'historial']) Historial de cambios</a>
        <a href="{{ route('admin.changelog.index') }}" class="{{ request()->routeIs('admin.changelog.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'versiones']) Historial de versiones</a>
      </div>

      {{-- Cuenta --}}
      <button type="button" class="admin-nav-group-toggle" :class="{ 'is-open': openGroup === 'cuenta' }" @click="openGroup = openGroup === 'cuenta' ? null : 'cuenta'">
        Cuenta
        @include('partials.admin-icon', ['name' => 'chevron'])
      </button>
      <div class="admin-nav-group" x-show="openGroup === 'cuenta'" x-collapse x-cloak>
        <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'usuarios']) Usuarios</a>
        <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">@include('partials.admin-icon', ['name' => 'ajustes']) Ajustes</a>
      </div>
    </nav>
    <div class="admin-nav-foot">
      @php($adminVersion = \App\Http\Controllers\Admin\ChangelogController::currentVersion())
      @if($adminVersion)
        <div class="mono" style="padding:2px 12px 10px;color:var(--text-muted);font-size:11px;letter-spacing:.03em;">v{{ $adminVersion }}</div>
      @endif
      <a href="{{ route('home') }}" target="_blank">@include('partials.admin-icon', ['name' => 'ver-sitio']) Ver sitio ↗</a>
      @if(auth()->user()->isAdmin())
        <form method="POST" action="{{ route('admin.cache.purge') }}">
          @csrf
          <button type="submit" class="admin-nav-foot-btn">@include('partials.admin-icon', ['name' => 'purgar']) Purgar caché</button>
        </form>
      @endif
      <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="admin-nav-foot-btn admin-nav-logout">@include('partials.admin-icon', ['name' => 'salir']) Cerrar sesión</button>
      </form>
    </div>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <button type="button" class="admin-hamburger" aria-label="Abrir menú" x-on:click="navOpen = true">
        <span></span><span></span><span></span>
      </button>
      <h1>@yield('title', 'Panel')</h1>
      @hasSection('topbar-actions')
        <div>@yield('topbar-actions')</div>
      @endif
    </div>
    <div class="admin-content">
      @if(session('status'))
        <div class="admin-flash">{{ session('status') }}</div>
      @endif
      @if(session('error'))
        <div class="admin-flash admin-flash-error">{{ session('error') }}</div>
      @endif
      @yield('content')
    </div>
  </div>
</div>

@yield('scripts')
</body>
</html>
