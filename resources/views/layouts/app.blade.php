<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Cyrex Store')</title>
<style>:root{--logo-height:{{ $logoHeight }}px;}</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=optional" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@yield('styles')
</head>
<body>

@include('partials.nav')

<main>
@yield('content')
</main>

<footer>
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="{{ asset('images/logo-horizontal.png') }}" alt="Cyrex Store" class="footer-logo">
        <p class="footer-tagline">Componentes y periféricos gamer en Bolivia. Santa Cruz y Cochabamba.</p>
        <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" class="footer-whatsapp-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8.9-.2.2-.3.2-.5.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5l.4-.4c.1-.1.2-.2.2-.4.1-.1 0-.3 0-.4C10.3 9.6 9.9 8.6 9.7 8.2c-.2-.4-.3-.3-.5-.3h-.4c-.1 0-.4.1-.6.3-.2.2-.8.8-.8 2s.9 2.3 1 2.4c.1.2 1.7 2.6 4.1 3.6.6.2 1 .4 1.4.5.6.2 1.1.1 1.5.1.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2-.1-.1-.2-.2-.5-.3z"/></svg>
          Escríbenos por WhatsApp
        </a>
      </div>

      <div class="footer-col">
        <h4>Tienda</h4>
        @foreach($footerCategories as $cat)
          <a href="{{ route('shop', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
        @endforeach
        <a href="{{ route('shop') }}">Ver todo el catálogo</a>
      </div>

      <div class="footer-col">
        <h4>Ayuda</h4>
        <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank">WhatsApp</a>
        <span class="footer-text">Santa Cruz y Cochabamba, Bolivia</span>
      </div>

      <div class="footer-col">
        <h4>Cyrex</h4>
        <a href="{{ route('home') }}">Inicio</a>
        <a href="{{ route('shop') }}">Tienda</a>
      </div>
    </div>

    <div class="footer-bottom">
      <span>© {{ date('Y') }} Cyrex Store. Todos los derechos reservados.</span>
      <span>Santa Cruz · Cochabamba · <a href="/admin" style="color:var(--text-muted);">Acceso administradores</a></span>
    </div>
  </div>
</footer>

@yield('scripts')
</body>
</html>
