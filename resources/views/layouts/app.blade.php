<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('partials.favicon')
@if(!empty($ga4MeasurementId))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4MeasurementId }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ $ga4MeasurementId }}');
</script>
@endif
<title>@yield('title', 'Cyrex Store')</title>
@hasSection('meta_description')
<meta name="description" content="@yield('meta_description')">
@endif

{{-- Open Graph / Twitter Card — sin esto, compartir un link (sobre todo
     por WhatsApp, que es como vende la tienda) mostraba una vista previa
     vacía/genérica en vez de la foto y el nombre del producto. og:title
     y og:description reutilizan las mismas secciones title/meta_description
     que cada página ya define (@yield se puede llamar más de una vez),
     así que no hace falta declarar el texto dos veces por página — solo
     og_image necesita una sección propia, con el logo como respaldo para
     páginas que no definen una foto puntual (home, tienda, etc.). --}}
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:site_name" content="Cyrex Store">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="@yield('title', 'Cyrex Store')">
@hasSection('meta_description')
<meta property="og:description" content="@yield('meta_description')">
@endif
<meta property="og:image" content="@yield('og_image', asset('images/logo-horizontal.png'))">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('title', 'Cyrex Store')">
@hasSection('meta_description')
<meta name="twitter:description" content="@yield('meta_description')">
@endif
<meta name="twitter:image" content="@yield('og_image', asset('images/logo-horizontal.png'))">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v={{ filemtime(public_path('css/fonts.css')) }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<style>:root{--logo-height:{{ $logoHeight }}px;--gold:{{ $accentColor ?? '#FFD900' }};}</style>
@if(($promoBarActive ?? null)?->custom_css)
<style>{!! $promoBarActive->custom_css !!}</style>
@endif
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>document.addEventListener('alpine:init', () => Alpine.store('promoModal', { open: false }));</script>
@yield('styles')
</head>
<body class="{{ ($reducedMotion ?? 'off') === 'on' ? 'motion-reduced' : '' }} {{ auth()->check() ? 'has-admin-bar' : '' }}" @if($promoEffect ?? null) data-promo-effect="{{ $promoEffect }}" @endif>

@include('partials.promo-bar')

@auth
  <div class="admin-session-bar">
    <span>👤 Estás logueado como <strong>{{ \App\Models\User::ROLES[auth()->user()->role] ?? auth()->user()->role }}</strong> ({{ auth()->user()->name }})</span>
    <div class="admin-session-bar-actions">
      <a href="{{ route('admin.dashboard') }}">Ir al panel</a>
      <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
      </form>
    </div>
  </div>
@endauth

@include('partials.nav')

<main>
@yield('content')
</main>

<footer>
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="{{ $logoUrl }}" alt="Cyrex Store" class="footer-logo" loading="lazy">
        <p class="footer-tagline">{{ $footerTagline }}</p>
        <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" class="footer-whatsapp-btn">
          @include('partials.whatsapp-icon')
          {{ $footerWhatsappBtnText }}
        </a>
        @if($whatsappCommunityUrl)
          <a href="{{ $whatsappCommunityUrl }}" target="_blank" rel="noopener" class="footer-community-btn">
            @include('partials.whatsapp-icon')
            {{ $whatsappCommunityBtnText }}
          </a>
        @endif
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

@if(request()->routeIs('home'))
  @include('partials.promo-modal')
@endif

<script src="{{ asset('js/product-tilt.js') }}?v={{ filemtime(public_path('js/product-tilt.js')) }}"></script>
<script src="{{ asset('js/nav-scroll.js') }}?v={{ filemtime(public_path('js/nav-scroll.js')) }}"></script>
<script src="{{ asset('js/social-rotator.js') }}?v={{ filemtime(public_path('js/social-rotator.js')) }}"></script>
<script src="{{ asset('js/marcas-mosaico.js') }}?v={{ filemtime(public_path('js/marcas-mosaico.js')) }}"></script>
<script src="{{ asset('js/hero-title-decode.js') }}?v={{ filemtime(public_path('js/hero-title-decode.js')) }}"></script>
<script src="{{ asset('js/product-image-zoom.js') }}?v={{ filemtime(public_path('js/product-image-zoom.js')) }}"></script>
@if($promoEffect ?? null)
  <script src="{{ asset('js/promo-effects.js') }}?v={{ filemtime(public_path('js/promo-effects.js')) }}"></script>
@endif
@yield('scripts')
</body>
</html>
