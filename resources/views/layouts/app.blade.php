<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
// Usado por el onload/onerror de toda foto de card (tienda, relacionados,
// CMS, armador) para el fade-in de .card-media img. Va accá arriba, antes
// que cualquier otro script, para que esté definido pase lo que pase,
// sin importar qué tan rápido termine de cargar una imagen chica.
//
// Si la imagen ya estaba en caché del navegador, agregar la clase
// is-loaded EN EL MISMO INSTANTE en que el elemento se creó/insertó no
// le da tiempo al navegador de pintar el opacity:0 inicial antes de
// saltar a 1 — la transición de CSS no tiene de dónde "arrancar" y no
// anima nada (se ve como que la foto aparece de golpe). Con dos
// requestAnimationFrame de por medio, el opacity:0 ya se pintó al menos
// una vez antes de agregar la clase, así que ahí sí el fade corre.
//
// El offsetWidth de acá abajo fuerza ese cálculo de estilo/layout de
// forma SINCRÓNICA (en vez de solo confiar en que dos rAF alcancen para
// que el navegador pinte solo) — en Safari/iOS se vio que a veces
// encimaba los dos requestAnimationFrame en el mismo frame (sobre todo
// con la imagen ya en caché de memoria, carga casi instantánea) y el
// fade se volvía a saltar. Forzar el reflow antes de encadenar los rAF
// deja el opacity:0 ya calculado pase lo que pase con el timing de los
// frames.
window.markCardImageLoaded = function (img) {
  void img.offsetWidth;
  requestAnimationFrame(function () {
    requestAnimationFrame(function () { img.classList.add('is-loaded'); });
  });
};
</script>
@include('partials.favicon')
@if(!empty($ga4MeasurementId) && !\App\Support\DemoMode::active(request()))
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
      @if(auth()->user()->isAdmin())
        {{-- Preferencia personal del admin (sesión, no Ajustes) para ver
             el sitio en solo Bs mientras atiende a un cliente — no afecta
             lo que ve el público. Ver App\Support\AdminCurrencyPref. --}}
        <form method="POST" action="{{ route('admin.currency-pref.toggle') }}">
          @csrf
          <label class="admin-currency-switch" title="Ver todo el sitio en solo Bs — no afecta lo que ve el público">
            <input type="checkbox" onchange="this.form.submit()" {{ session('admin_force_bob', false) ? 'checked' : '' }}>
            <span class="admin-currency-switch-track"></span>
            <span>Solo Bs</span>
          </label>
        </form>
      @endif
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
        <a href="{{ route('saved-builds.index') }}">Armados de la comunidad</a>
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

@include('partials.exchange-rate-float')

<script src="{{ asset('js/exchange-rate-float.js') }}?v={{ filemtime(public_path('js/exchange-rate-float.js')) }}"></script>
<script src="{{ asset('js/product-tilt.js') }}?v={{ filemtime(public_path('js/product-tilt.js')) }}"></script>
<script src="{{ asset('js/hero-video-parallax.js') }}?v={{ filemtime(public_path('js/hero-video-parallax.js')) }}"></script>
<script src="{{ asset('js/hero-video-youtube.js') }}?v={{ filemtime(public_path('js/hero-video-youtube.js')) }}"></script>
<script src="{{ asset('js/nav-scroll.js') }}?v={{ filemtime(public_path('js/nav-scroll.js')) }}"></script>
<script src="{{ asset('js/nav-link-indicator.js') }}?v={{ filemtime(public_path('js/nav-link-indicator.js')) }}"></script>
<script src="{{ asset('js/social-rotator.js') }}?v={{ filemtime(public_path('js/social-rotator.js')) }}"></script>
<script src="{{ asset('js/scroll-reveal.js') }}?v={{ filemtime(public_path('js/scroll-reveal.js')) }}"></script>
<script src="{{ asset('js/marcas-mosaico.js') }}?v={{ filemtime(public_path('js/marcas-mosaico.js')) }}"></script>
<script src="{{ asset('js/marcas-track.js') }}?v={{ filemtime(public_path('js/marcas-track.js')) }}"></script>
<script src="{{ asset('js/hero-title-decode.js') }}?v={{ filemtime(public_path('js/hero-title-decode.js')) }}"></script>
<script src="{{ asset('js/product-image-zoom.js') }}?v={{ filemtime(public_path('js/product-image-zoom.js')) }}"></script>
<script src="{{ asset('js/brand-hero-tilt.js') }}?v={{ filemtime(public_path('js/brand-hero-tilt.js')) }}"></script>
<script src="{{ asset('js/brand-anchor-scroll.js') }}?v={{ filemtime(public_path('js/brand-anchor-scroll.js')) }}"></script>
{{-- Global (no @section('scripts') en shop.blade.php): shop-ajax.js escucha
     clicks delegados en document y busca .shop-main en cada uno, así que es
     inofensivo en páginas sin tienda. Cargarlo solo desde shop.blade.php
     hacía que nunca se cargara al llegar a /tienda por navegación suave
     (page-nav.js solo reemplaza <main>, nunca vuelve a pedir la página
     completa) — el listener de orden/filtro/paginación simplemente no
     existía hasta un F5 real. --}}
<script src="{{ asset('js/shop-ajax.js') }}?v={{ filemtime(public_path('js/shop-ajax.js')) }}"></script>
<script src="{{ asset('js/page-nav.js') }}?v={{ filemtime(public_path('js/page-nav.js')) }}"></script>
@if($promoEffect ?? null)
  <script src="{{ asset('js/promo-effects.js') }}?v={{ filemtime(public_path('js/promo-effects.js')) }}"></script>
@endif
@unless(\App\Support\DemoMode::active(request()))
<script>
// Sin esto, alguien que se queda leyendo una sola página sin hacer clic
// "desaparece" de Conectados ahora en Admin > Analítica a los 5 minutos
// aunque siga ahí — solo refresca la marca de actividad, no cuenta como
// una página vista nueva. Se detiene solo si la pestaña queda en segundo
// plano, para no inflar el conteo con pestañas abiertas sin mirar.
setInterval(() => {
  if (document.visibilityState === 'visible') {
    fetch('{{ route('visit.heartbeat') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    });
  }
}, 60000);
</script>
@endunless
<script src="{{ asset('js/demo-mode.js') }}?v={{ filemtime(public_path('js/demo-mode.js')) }}"></script>
@yield('scripts')
</body>
</html>
