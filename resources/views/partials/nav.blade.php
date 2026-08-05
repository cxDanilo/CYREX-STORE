<div x-data="{ megaOpen: false, mobileOpen: false }" x-on:keydown.escape.window="megaOpen = false; mobileOpen = false">

<nav>
  <div class="wrap nav-inner">
    <button type="button" class="hamburger" x-on:click="mobileOpen = true" aria-label="Abrir menú">
      <span></span><span></span><span></span>
    </button>

    <div class="logo"><a href="{{ route('home') }}" style="color:inherit;">CYREX<span>.</span></a></div>

    <div class="nav-cats" x-on:click.outside="megaOpen = false">
      <button type="button" class="nav-cats-btn" x-on:click="megaOpen = !megaOpen" :class="megaOpen && 'active'">
        Categorías
        <svg width="10" height="6" viewBox="0 0 10 6" fill="none" :style="megaOpen && 'transform:rotate(180deg)'" style="transition:transform .2s;">
          <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
        </svg>
      </button>

      <div class="mega-menu" x-show="megaOpen" x-cloak>
        @foreach($navCategories as $parent)
          <div class="mega-col">
            <div class="mega-col-title">
              <span class="mega-icon">@include('partials.category-icon', ['icon' => $parent->icon])</span>
              <a href="{{ route('shop', ['category' => $parent->slug]) }}">{{ $parent->name }}</a>
            </div>
            <div class="mega-col-links">
              @foreach($parent->children as $child)
                <a href="{{ route('shop', ['category' => $child->slug]) }}">{{ $child->name }}</a>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <form class="nav-search" method="GET" action="{{ route('shop') }}">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar en todo Cyrex Store" />
      <button class="search-btn" type="submit">Buscar</button>
    </form>

    <div class="nav-actions">
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

</div>
