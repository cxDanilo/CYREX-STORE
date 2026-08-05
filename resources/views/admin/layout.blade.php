<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Admin') — Cyrex Store</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>

<div class="admin-body">
  <aside class="admin-sidebar">
    <div class="admin-logo"><a href="{{ route('admin.productos.index') }}" style="color:inherit;">CYREX<span>.</span> ADMIN</a></div>
    <nav class="admin-nav">
      <a href="{{ route('admin.productos.index') }}" class="{{ request()->routeIs('admin.productos.*') ? 'active' : '' }}">Productos</a>
      <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">Usuarios</a>
      <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">Ajustes</a>
    </nav>
    <div class="admin-nav-foot">
      <a href="{{ route('home') }}" target="_blank">Ver sitio ↗</a>
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
      @yield('content')
    </div>
  </div>
</div>

</body>
</html>
