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
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="{{ asset('js/admin-autoslug.js') }}?v={{ filemtime(public_path('js/admin-autoslug.js')) }}"></script>
@yield('styles')
</head>
<body>

<div class="admin-body">
  <aside class="admin-sidebar">
    <div class="admin-logo"><a href="{{ route('admin.dashboard') }}" style="color:inherit;">CYREX<span>.</span> ADMIN</a></div>
    <nav class="admin-nav">
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>

      <div class="admin-nav-label">Catálogo</div>
      <a href="{{ route('admin.productos.index') }}" class="{{ request()->routeIs('admin.productos.*') ? 'active' : '' }}">Productos</a>
      <a href="{{ route('admin.categorias.index') }}" class="{{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}">Categorías</a>
      <a href="{{ route('admin.promociones.index') }}" class="{{ request()->routeIs('admin.promociones.*') ? 'active' : '' }}">Promociones</a>
      <a href="{{ route('admin.historial.index') }}" class="{{ request()->routeIs('admin.historial.*') ? 'active' : '' }}">Historial de cambios</a>
      <a href="{{ route('admin.changelog.index') }}" class="{{ request()->routeIs('admin.changelog.*') ? 'active' : '' }}">Historial de versiones</a>
      @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.woocommerce.create') }}" class="{{ request()->routeIs('admin.woocommerce.*') ? 'active' : '' }}">Importar WooCommerce</a>
      @endif
      <a href="{{ route('admin.pc-builder-options.index') }}" class="{{ request()->routeIs('admin.pc-builder-options.*') ? 'active' : '' }}">Compatibilidad (Arma tu PC)</a>

      <div class="admin-nav-label">Contenido</div>
      <a href="{{ route('admin.paginas.index') }}" class="{{ request()->routeIs('admin.paginas.*') ? 'active' : '' }}">Páginas</a>
      <a href="{{ route('admin.plantillas.index') }}" class="{{ request()->routeIs('admin.plantillas.*') ? 'active' : '' }}">Plantillas</a>
      <a href="{{ route('admin.medios.index') }}" class="{{ request()->routeIs('admin.medios.*') ? 'active' : '' }}">Medios</a>
      <a href="{{ route('admin.menus.index') }}" class="{{ request()->routeIs('admin.menus.*') ? 'active' : '' }}">Menús</a>
      <a href="{{ route('admin.redes.index') }}" class="{{ request()->routeIs('admin.redes.*') ? 'active' : '' }}">Redes sociales</a>

      <div class="admin-nav-label">Cuenta</div>
      <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">Usuarios</a>
      <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">Ajustes</a>
    </nav>
    <div class="admin-nav-foot">
      @php($adminVersion = \App\Http\Controllers\Admin\ChangelogController::currentVersion())
      @if($adminVersion)
        <div class="mono" style="padding:10px 12px 0;color:var(--text-muted);font-size:11px;letter-spacing:.03em;">v{{ $adminVersion }}</div>
      @endif
      <a href="{{ route('home') }}" target="_blank">Ver sitio ↗</a>
      @if(auth()->user()->isAdmin())
        <form method="POST" action="{{ route('admin.cache.purge') }}">
          @csrf
          <button type="submit" style="background:none;border:none;padding:10px 12px;color:var(--text-muted);font-size:13px;cursor:pointer;width:100%;text-align:left;">Purgar caché</button>
        </form>
      @endif
      <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" style="background:none;border:none;padding:10px 12px;color:var(--text-muted);font-size:13px;cursor:pointer;width:100%;text-align:left;">Cerrar sesión</button>
      </form>
    </div>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
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
