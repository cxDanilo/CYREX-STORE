<div class="cart-drawer-overlay" x-show="$store.cart.open" x-transition.opacity.duration.200ms x-on:click="$store.cart.open = false" x-cloak></div>

<aside class="cart-drawer" :class="$store.cart.open && 'open'">
  <div class="mobile-drawer-head">
    <div class="logo">Carrito<span>.</span></div>
    <button type="button" class="mobile-drawer-close" x-on:click="$store.cart.open = false" aria-label="Cerrar carrito">×</button>
  </div>

  <div id="cart-drawer-content">
    @include('partials.cart-drawer-content')
  </div>
</aside>
