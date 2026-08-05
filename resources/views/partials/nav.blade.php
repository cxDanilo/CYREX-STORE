<script>
document.addEventListener('alpine:init', () => {
  Alpine.store('cart', {
    keys: @json($cartItems->pluck('key')->values()),
    count: {{ $cartCount }},
    open: false,

    has(productId, variantId) {
      return this.keys.includes(productId + ':' + (variantId ?? ''));
    },

    async add(productId, variantId) {
      const res = await fetch('{{ route('cart.add') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ product_id: productId, variant_id: variantId }),
      });
      const data = await res.json();
      this.apply(data);
      this.open = true;
    },

    async remove(key) {
      const res = await fetch('/carrito/quitar/' + encodeURIComponent(key), {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
      });
      const data = await res.json();
      this.apply(data);
    },

    apply(data) {
      const prevCount = this.count;
      this.keys = data.keys;
      this.count = data.count;

      const el = document.getElementById('cart-drawer-content');
      if (el) {
        el.style.opacity = '0';
        setTimeout(() => {
          el.innerHTML = data.html;
          el.style.opacity = '1';
        }, 150);
      }

      if (data.count !== prevCount) {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
          badge.classList.remove('pulse');
          void badge.offsetWidth;
          badge.classList.add('pulse');
        }
      }
    },
  });
});
</script>

<div x-data="{ mobileOpen: false }" x-on:keydown.escape.window="mobileOpen = false; $store.cart.open = false">

<nav>
  <div class="wrap nav-inner">
    <button type="button" class="hamburger" x-on:click="mobileOpen = true" aria-label="Abrir menú">
      <span></span><span></span><span></span>
    </button>

    <div class="logo"><a href="{{ route('home') }}" style="color:inherit;"><img src="{{ asset('images/logo-icon.png') }}" alt="" class="logo-icon">CYREX<span>.</span></a></div>

    <a href="{{ route('home') }}" class="nav-home-link">Inicio</a>
    <a href="{{ route('shop') }}" class="nav-home-link">Tienda</a>

    @include('partials.search-box')

    <div class="nav-actions">
      <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" class="nav-whatsapp-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8.9-.2.2-.3.2-.5.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5l.4-.4c.1-.1.2-.2.2-.4.1-.1 0-.3 0-.4C10.3 9.6 9.9 8.6 9.7 8.2c-.2-.4-.3-.3-.5-.3h-.4c-.1 0-.4.1-.6.3-.2.2-.8.8-.8 2s.9 2.3 1 2.4c.1.2 1.7 2.6 4.1 3.6.6.2 1 .4 1.4.5.6.2 1.1.1 1.5.1.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2-.1-.1-.2-.2-.5-.3z"/></svg>
        <span>Escríbenos</span>
      </a>
      <span class="nav-status"><i></i> En línea</span>
      <button type="button" class="cart-icon-btn" x-on:click="$store.cart.open = true" aria-label="Ver carrito">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M3 4h2l2.4 12.4a2 2 0 0 0 2 1.6h7.2a2 2 0 0 0 2-1.6L20 8H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="10" cy="20" r="1.4" fill="currentColor"/>
          <circle cx="17" cy="20" r="1.4" fill="currentColor"/>
        </svg>
        <span class="cart-badge" x-show="$store.cart.count > 0" x-text="$store.cart.count" x-cloak></span>
      </button>
      <a href="{{ route('shop') }}" class="btn-gold" style="text-decoration:none;">Ver tienda</a>
    </div>
  </div>
</nav>

<div class="mobile-drawer-overlay" x-show="mobileOpen" x-transition.opacity.duration.200ms x-on:click="mobileOpen = false" x-cloak></div>

<aside class="mobile-drawer" :class="mobileOpen && 'open'" x-data="{ openGroup: null }">
  <div class="mobile-drawer-head">
    <div class="logo"><img src="{{ asset('images/logo-icon.png') }}" alt="" class="logo-icon">CYREX<span>.</span></div>
    <button type="button" class="mobile-drawer-close" x-on:click="mobileOpen = false" aria-label="Cerrar menú">×</button>
  </div>

  @include('partials.search-box', ['formClass' => 'mobile-search'])

  <nav class="mobile-accordion">
    @foreach($navCategories as $parent)
      <div class="accordion-group">
        <button type="button" class="accordion-head" x-on:click="openGroup = (openGroup === {{ $parent->id }} ? null : {{ $parent->id }})">
          <span style="display:flex;align-items:center;gap:10px;">
            <span class="mega-icon">@include('partials.category-icon', ['icon' => $parent->icon])</span>
            {{ $parent->name }}
          </span>
          <svg width="10" height="6" viewBox="0 0 10 6" fill="none" :style="openGroup === {{ $parent->id }} && 'transform:rotate(180deg)'" style="transition:transform .2s;flex-shrink:0;">
            <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
          </svg>
        </button>
        <div class="accordion-body" x-show="openGroup === {{ $parent->id }}" x-transition.opacity.duration.150ms x-cloak>
          <a href="{{ route('shop', ['category' => $parent->slug]) }}">Ver todo en {{ $parent->name }}</a>
          @foreach($parent->children as $child)
            <a href="{{ route('shop', ['category' => $child->slug]) }}">{{ $child->name }}</a>
          @endforeach
        </div>
      </div>
    @endforeach
  </nav>
</aside>

@include('partials.cart-drawer')

</div>

@if($categoryMenuScope === 'all' || request()->routeIs('shop'))
  @include('partials.category-float')
@endif
