<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Cyrex Store')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@yield('styles')
</head>
<body>

<nav>
  <div class="wrap nav-inner">
    <div class="logo"><a href="{{ route('home') }}" style="color:inherit;">CYREX<span>.</span></a></div>
    <div class="nav-search">
      <input type="text" placeholder="Buscar en todo Cyrex Store" />
      <button class="search-btn">Buscar</button>
    </div>
    <div class="nav-actions">
      <a href="{{ route('shop') }}" class="btn-gold" style="text-decoration:none;">Ver tienda</a>
    </div>
  </div>
</nav>

<main>
@yield('content')
</main>

<footer class="wrap">
  <div class="footer-top">
    <div>
      <div class="logo" style="margin-bottom:10px;">CYREX<span>.</span></div>
      <p style="color:var(--text-secondary);font-size:13.5px;max-width:260px;">Componentes y periféricos gamer en Bolivia. Santa Cruz y Cochabamba.</p>
    </div>
    <div class="footer-cols">
      <div class="footer-col">
        <h4>Tienda</h4>
        <a href="{{ route('shop') }}">Ver catálogo</a>
      </div>
      <div class="footer-col">
        <h4>Cyrex</h4>
        <a href="{{ route('home') }}">Inicio</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© {{ date('Y') }} Cyrex Store. Todos los derechos reservados.</span>
    <span>Santa Cruz · Cochabamba · <a href="/admin" style="color:var(--text-muted);">Acceso administradores</a></span>
  </div>
</footer>

@yield('scripts')
</body>
</html>
