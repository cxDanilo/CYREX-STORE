<script>
document.addEventListener('alpine:init', () => {
  Alpine.store('cart', {
    keys: @json($cartItems->pluck('key')->values()),
    count: {{ $cartCount }},
    open: false,

    has(productId, variantId) {
      return this.keys.includes(productId + ':' + (variantId ?? ''));
    },

    hasCombo(comboId) {
      return this.keys.includes('combo:' + comboId);
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

    async addCombo(comboId) {
      const res = await fetch('{{ route('cart.add-combo') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ combo_id: comboId }),
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

  // Reloj único compartido por todas las tarjetas en oferta de la
  // página — mismo patrón de cuenta regresiva que partials/promo-bar.php,
  // pero con un solo setInterval en vez de uno por tarjeta (podría haber
  // varias docenas de productos en oferta contando la misma fecha a la
  // vez). Alpine llama a init() solo, apenas se registra el store.
  Alpine.store('offer', {
    active: {{ $offerActive ? 'true' : 'false' }},
    endsAt: @js($offerEndsAtIso),
    remaining: '',
    // d/h/m/s ya separados y con padding — el contador grande de la
    // página de producto los usa para armar los "dígitos" propios, en
    // vez de parsear el string remaining con una regex.
    d: '00', h: '00', m: '00', s: '00',
    init() {
      if (this.active && this.endsAt) {
        this.tick();
        setInterval(() => this.tick(), 1000);
      }
    },
    tick() {
      const diff = Math.max(0, new Date(this.endsAt) - new Date());
      const d = Math.floor(diff / 86400000);
      const h = Math.floor((diff % 86400000) / 3600000);
      const m = Math.floor((diff % 3600000) / 60000);
      const s = Math.floor((diff % 60000) / 1000);
      this.d = String(d).padStart(2, '0');
      this.h = String(h).padStart(2, '0');
      this.m = String(m).padStart(2, '0');
      this.s = String(s).padStart(2, '0');
      this.remaining = (d > 0 ? d + 'd ' : '') + this.h + 'h ' + this.m + 'm ' + this.s + 's';
    },
  });

  // Calendario propio para elegir la fecha de fin de una oferta desde
  // la edición rápida pública — reemplaza el <input type=datetime-local>
  // nativo (cada navegador/SO lo dibuja distinto y no se puede tematizar)
  // por un widget hecho a mano. Lee y escribe directamente la propiedad
  // "editEndsAt" del x-data padre (mismo formato 'YYYY-MM-DDTHH:mm' que
  // ya espera saveQuickEdit()).
  Alpine.data('offerDatePicker', () => ({
    open: false,
    viewYear: 0,
    viewMonth: 0,
    selHour: '00',
    selMinute: '00',
    weekdays: ['L', 'M', 'X', 'J', 'V', 'S', 'D'],
    monthNames: ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
    init() {
      const base = this.editEndsAt ? new Date(this.editEndsAt) : new Date();
      this.viewYear = base.getFullYear();
      this.viewMonth = base.getMonth();
      if (this.editEndsAt) {
        this.selHour = String(base.getHours()).padStart(2, '0');
        this.selMinute = String(Math.round(base.getMinutes() / 5) * 5 % 60).padStart(2, '0');
      }
    },
    get monthLabel() {
      const name = this.monthNames[this.viewMonth];
      return name.charAt(0).toUpperCase() + name.slice(1) + ' de ' + this.viewYear;
    },
    get displayLabel() {
      if (!this.editEndsAt) return 'Elegí fecha y hora';
      const d = new Date(this.editEndsAt);
      const pad = n => String(n).padStart(2, '0');
      return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    },
    get days() {
      const first = new Date(this.viewYear, this.viewMonth, 1);
      const startOffset = (first.getDay() + 6) % 7;
      const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
      const cells = [];
      for (let i = 0; i < startOffset; i++) cells.push(null);
      for (let day = 1; day <= daysInMonth; day++) cells.push(day);
      while (cells.length % 7 !== 0) cells.push(null);
      return cells;
    },
    prevMonth() { this.viewMonth--; if (this.viewMonth < 0) { this.viewMonth = 11; this.viewYear--; } },
    nextMonth() { this.viewMonth++; if (this.viewMonth > 11) { this.viewMonth = 0; this.viewYear++; } },
    isSelected(day) {
      if (!day || !this.editEndsAt) return false;
      const d = new Date(this.editEndsAt);
      return d.getFullYear() === this.viewYear && d.getMonth() === this.viewMonth && d.getDate() === day;
    },
    isToday(day) {
      if (!day) return false;
      const t = new Date();
      return t.getFullYear() === this.viewYear && t.getMonth() === this.viewMonth && t.getDate() === day;
    },
    pickDay(day) {
      if (!day) return;
      const pad = n => String(n).padStart(2, '0');
      this.editEndsAt = `${this.viewYear}-${pad(this.viewMonth + 1)}-${pad(day)}T${this.selHour}:${this.selMinute}`;
    },
    applyTime() {
      if (!this.editEndsAt) return;
      const d = new Date(this.editEndsAt);
      const pad = n => String(n).padStart(2, '0');
      this.editEndsAt = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${this.selHour}:${this.selMinute}`;
    },
    setToday() {
      const t = new Date();
      this.viewYear = t.getFullYear();
      this.viewMonth = t.getMonth();
      const pad = n => String(n).padStart(2, '0');
      this.selHour = pad(t.getHours());
      this.selMinute = pad(Math.round(t.getMinutes() / 5) * 5 % 60);
      this.editEndsAt = `${t.getFullYear()}-${pad(t.getMonth() + 1)}-${pad(t.getDate())}T${this.selHour}:${this.selMinute}`;
    },
    clear() { this.editEndsAt = ''; this.open = false; },
  }));
});
</script>

<div x-data="{ mobileOpen: false }" x-on:keydown.escape.window="mobileOpen = false; $store.cart.open = false">

<nav class="{{ request()->routeIs('home') ? 'nav-hero-mode' : '' }}">
  <div class="wrap nav-inner">
    <button type="button" class="hamburger" x-on:click="mobileOpen = true" aria-label="Abrir menú">
      <span></span><span></span><span></span>
    </button>

    <div class="logo"><a href="{{ route('home') }}"><img src="{{ $logoUrl }}" alt="Cyrex Store" class="logo-full"></a></div>

    @foreach($headerMenuItems as $item)
      <a href="{{ $item['url'] }}" class="nav-home-link">{{ $item['label'] }}</a>
    @endforeach

    @include('partials.search-box')

    <div class="nav-actions">
      <button type="button" class="cart-icon-btn" x-on:click="$store.cart.open = true" aria-label="Ver carrito">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M3 4h2l2.4 12.4a2 2 0 0 0 2 1.6h7.2a2 2 0 0 0 2-1.6L20 8H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="10" cy="20" r="1.4" fill="currentColor"/>
          <circle cx="17" cy="20" r="1.4" fill="currentColor"/>
        </svg>
        <span class="cart-badge" x-show="$store.cart.count > 0" x-text="$store.cart.count" x-cloak></span>
      </button>
      <a href="{{ route('pc-builder') }}" class="btn-outline-gold">Arma tu PC</a>
    </div>
  </div>
</nav>

<div class="mobile-drawer-overlay" x-show="mobileOpen" x-transition.opacity.duration.200ms x-on:click="mobileOpen = false" x-cloak></div>

{{-- Nadie cerraba el drawer al tocar un link de adentro (categoría,
     "Arma tu PC", una sugerencia del buscador) — la navegación (real o
     suave vía page-nav.js) pasaba bien, pero el panel se quedaba
     abierto tapando la página nueva, porque vive fuera de <main> y
     nada lo tocaba durante ese cambio de página. El handler va en todo
     el <aside> (no solo en el acordeón de categorías) para cubrir
     cualquier link de acá adentro de una sola vez — el acordeón en sí
     abre/cierra con su propio botón, no con estos links, así que un
     click en un <a> siempre significa navegación real. --}}
<aside class="mobile-drawer" :class="mobileOpen && 'open'" x-data="{ openGroup: null }" x-on:click="$event.target.closest('a') && (mobileOpen = false)">
  <div class="mobile-drawer-head">
    <img src="{{ $logoUrl }}" alt="Cyrex Store" class="logo-full">
    <button type="button" class="mobile-drawer-close" x-on:click="mobileOpen = false" aria-label="Cerrar menú">×</button>
  </div>

  @include('partials.search-box', ['formClass' => 'mobile-search'])

  <a href="{{ route('pc-builder') }}" class="btn-outline-gold" style="width:100%;justify-content:center;margin:14px 0;">Arma tu PC</a>

  <nav class="mobile-accordion">
    @foreach($navCategories as $parent)
      <div class="accordion-group" :class="openGroup === {{ $parent->id }} && 'is-open'">
        <button type="button" class="accordion-head" x-on:click="openGroup = (openGroup === {{ $parent->id }} ? null : {{ $parent->id }})">
          <span class="accordion-head-label">
            <span class="mega-icon">@include('partials.category-icon', ['icon' => $parent->icon, 'iconImage' => $parent->icon_image_url])</span>
            <span class="accordion-head-text">{{ $parent->name }}</span>
          </span>
          <svg width="10" height="6" viewBox="0 0 10 6" fill="none" :style="openGroup === {{ $parent->id }} && 'transform:rotate(180deg)'" style="transition:transform .2s;flex-shrink:0;">
            <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
          </svg>
        </button>
        <div class="accordion-body" x-show="openGroup === {{ $parent->id }}" x-collapse.duration.250ms x-cloak>
          <a href="{{ route('shop', ['category' => $parent->slug]) }}" class="accordion-viewall">Ver todo en {{ $parent->name }}</a>
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

{{-- Siempre se incluye (aunque el scope sea "shop" y esta página no
     sea /tienda) — la visibilidad se resuelve en category-float.blade
     con un inline-style calculado en el request real. Si se dejara
     afuera del todo cuando no corresponde, page-nav.js jamás podría
     "hacerlo aparecer" en una navegación suave hacia /tienda: ese
     script solo reemplaza el <main>, nunca vuelve a pedir/renderizar
     este <nav> — por eso el botón se quedaba invisible para siempre
     después de llegar a /tienda navegando (en vez de con una carga
     real) hasta que se recargaba la página entera. --}}
@include('partials.category-float')
