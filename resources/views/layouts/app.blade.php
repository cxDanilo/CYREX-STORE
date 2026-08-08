<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Cyrex Store')</title>
@hasSection('meta_description')
<meta name="description" content="@yield('meta_description')">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=optional" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<style>:root{--logo-height:{{ $logoHeight }}px;--gold:{{ $accentColor ?? '#FFD900' }};}</style>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@yield('styles')
</head>
<body class="{{ ($reducedMotion ?? 'off') === 'on' ? 'motion-reduced' : '' }}">

@include('partials.nav')

<main>
@yield('content')
</main>

<footer>
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="{{ $logoUrl }}" alt="Cyrex Store" class="footer-logo">
        <p class="footer-tagline">{{ $footerTagline }}</p>
        <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" class="footer-whatsapp-btn">
          @include('partials.whatsapp-icon')
          {{ $footerWhatsappBtnText }}
        </a>
        @if($socialLinks->isNotEmpty())
          <div class="footer-social">
            @foreach($socialLinks as $link)
              <a href="{{ $link->url }}" target="_blank" rel="noopener" class="footer-social-icon" aria-label="{{ $link->platform }}">
                @include('partials.social-icon', ['platform' => $link->platform])
              </a>
            @endforeach
          </div>
        @endif
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
        @foreach($footerPages as $page)
          <a href="{{ route('page.show', $page->slug) }}">{{ $page->title }}</a>
        @endforeach
      </div>
    </div>

    <div class="footer-bottom">
      <span>© {{ date('Y') }} Cyrex Store. Todos los derechos reservados.</span>
      <span>Santa Cruz · Cochabamba · <a href="/admin" style="color:var(--text-muted);">Acceso administradores</a></span>
    </div>
  </div>
</footer>

<script src="{{ asset('js/product-tilt.js') }}?v={{ filemtime(public_path('js/product-tilt.js')) }}"></script>
<script src="{{ asset('js/nav-scroll.js') }}?v={{ filemtime(public_path('js/nav-scroll.js')) }}"></script>
<script src="{{ asset('js/social-rotator.js') }}?v={{ filemtime(public_path('js/social-rotator.js')) }}"></script>
<script src="{{ asset('js/hero-title-decode.js') }}?v={{ filemtime(public_path('js/hero-title-decode.js')) }}"></script>
<script src="{{ asset('js/product-image-zoom.js') }}?v={{ filemtime(public_path('js/product-image-zoom.js')) }}"></script>
@yield('scripts')
</body>
</html>
