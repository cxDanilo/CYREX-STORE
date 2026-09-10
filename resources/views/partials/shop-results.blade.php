@php
  $sortOptions = [
    'predeterminado' => 'Orden predeterminado',
    'precio_asc' => 'Precio: menor a mayor',
    'precio_desc' => 'Precio: mayor a menor',
    'nombre_az' => 'Nombre A-Z',
  ];
  $currentSort = request('orden', 'predeterminado');
@endphp
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
  <div style="color:var(--text-secondary);font-size:14px;">
    <b style="color:var(--text-primary);">{{ $products->total() }}</b> productos
  </div>
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
    <div class="shop-sort" x-data="{ open:false }" @click.outside="open=false">
      <button type="button" class="shop-sort-trigger" @click="open=!open" :aria-expanded="open.toString()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <span>{{ $sortOptions[$currentSort] }}</span>
        <svg class="shop-sort-caret" :class="{ 'is-open': open }" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5"/></svg>
      </button>
      <div class="shop-sort-menu" x-show="open" x-transition.opacity.scale.95.duration.150ms x-cloak @click="open=false">
        @foreach($sortOptions as $key => $label)
          <a href="{{ route('shop', array_merge(request()->except(['orden', 'page']), $key === 'predeterminado' ? [] : ['orden' => $key])) }}"
             class="shop-sort-option {{ $currentSort === $key ? 'active' : '' }}">
            {{ $label }}
            @if($currentSort === $key)
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @endif
          </a>
        @endforeach
      </div>
    </div>
    @if($activeCategory)
      {{-- data-no-ajax: shop-ajax.js no puede manejar este link (cambia
           de categoría, y su swap de solo el fragmento no toca el
           encabezado de arriba). data-page-nav: aun así queremos que
           page-nav.js sí lo intercepte (su swap completo del <main> sí
           actualiza el encabezado bien) — sin este segundo atributo,
           page-nav.js lo excluiría igual por estar dentro de
           .shop-main, junto con orden/filtro/paginación. --}}
      <a href="{{ route('shop') }}" class="btn btn-sm" data-no-ajax data-page-nav>Ver todo el catálogo ×</a>
    @endif
  </div>
</div>

@if($filterField)
  <div class="shop-filter-row">
    <span class="shop-toolbar-label">{{ $filterLabel }}</span>
    @foreach($filterOptions as $value => $label)
      <a href="{{ route('shop', array_merge(request()->except(['attr', 'page']), ['attr' => $value])) }}"
         class="shop-filter-pill {{ request('attr') === (string) $value ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
    @if(request()->filled('attr'))
      <a href="{{ route('shop', request()->except(['attr', 'page'])) }}" class="shop-filter-pill shop-filter-clear">Quitar filtro ×</a>
    @endif
  </div>
@endif

<div class="product-grid">
  @forelse($products as $product)
    @include('partials.product-card', ['product' => $product, 'quickAdd' => true, 'rate' => $rate ?? null, 'forceBob' => $forceBob ?? false])
  @empty
    <p style="color:var(--text-secondary);">No hay productos en esta categoría todavía.</p>
  @endforelse

  @php
    $helpWaText = $activeCategory
      ? "Hola! Buscaba algo en \"{$activeCategory->name}\" y no lo encontré en la tienda. ¿Me pueden ayudar?"
      : 'Hola! No encontré lo que buscaba en la tienda. ¿Me pueden ayudar?';
  @endphp
  <a class="card card-help" href="https://wa.me/{{ \App\Support\ReferralRouter::whatsappNumber() }}?text={{ urlencode($helpWaText) }}" target="_blank" rel="noopener">
    <div class="card-help-media">@include('partials.whatsapp-icon')</div>
    <div class="card-body">
      <div class="card-name">¿No encuentras lo que buscas?</div>
      <div class="card-help-cta">Nosotros te lo traemos →</div>
    </div>
  </a>
</div>

<div class="pagination-links">
  {{ $products->links('partials.pagination') }}
</div>
