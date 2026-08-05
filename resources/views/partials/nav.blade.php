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
      this.keys = data.keys;
      this.count = data.count;
      const el = document.getElementById('cart-drawer-content');
      if (el) el.innerHTML = data.html;
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

    <div class="logo"><a href="{{ route('home') }}" style="color:inherit;">CYREX<span>.</span></a></div>

    <form class="nav-search" method="GET" action="{{ route('shop') }}">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar en todo Cyrex Store" />
      <button class="search-btn" type="submit">Buscar</button>
    </form>

    <div class="nav-actions">
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

<div class="mobile-drawer-overlay" x-show="mobileOpen" x-on:click="mobileOpen = false" x-cloak></div>

<aside class="mobile-drawer" :class="mobileOpen && 'open'" x-data="{ openGroup: null }">
  <div class="mobile-drawer-head">
    <div class="logo">CYREX<span>.</span></div>
    <button type="button" class="mobile-drawer-close" x-on:click="mobileOpen = false" aria-label="Cerrar menú">×</button>
  </div>

  <form class="mobile-search" method="GET" action="{{ route('shop') }}">
    <input type="text" name="q" placeholder="Buscar en todo Cyrex Store" />
    <button type="submit" class="search-btn">Buscar</button>
  </form>

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
        <div class="accordion-body" x-show="openGroup === {{ $parent->id }}" x-cloak>
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
