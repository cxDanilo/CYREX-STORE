@php
  $sortOptions = [
    'predeterminado' => 'Orden predeterminado',
    'precio_asc' => 'Precio: menor a mayor',
    'precio_desc' => 'Precio: mayor a menor',
    'recientes' => 'Más recientes',
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
      <a href="{{ route('shop') }}" class="btn btn-sm" data-no-ajax>Ver todo el catálogo ×</a>
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
    <a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;"
       x-data="{
         showVariants: false,
         hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
         variants: {{ $product->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->variant_value])->toJson() }},
         inStock: {{ $product->is_sold_out ? 'false' : 'true' }},
         quickAdd() {
           if (!this.inStock) return;
           if (this.hasVariants) { this.showVariants = !this.showVariants; }
           else { $store.cart.add({{ $product->id }}, null); }
         }
       }"
       @click.outside="showVariants = false">
      <div class="card-media">
        @if($product->image_url)
          <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" style="{{ $product->is_sold_out ? 'filter:grayscale(1);' : '' }}">
        @endif
        @if($product->is_sold_out)
          <span class="card-badge-agotado">Agotado</span>
        @endif
        <button type="button" class="card-quick-add" :disabled="!inStock" @click.stop.prevent="quickAdd()" aria-label="Agregar {{ $product->name }} al carrito">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.4 12.4a2 2 0 0 0 2 1.6h7.2a2 2 0 0 0 2-1.6L20 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.3" fill="currentColor"/><circle cx="17" cy="20" r="1.3" fill="currentColor"/></svg>
        </button>

        @if($product->has_variants)
          <div class="card-variant-picker" x-show="showVariants" x-cloak x-transition.opacity.duration.150ms @click.stop.prevent="true">
            <div class="card-variant-picker-title">Elegí una opción</div>
            <div class="card-variant-picker-options">
              <template x-for="v in variants" :key="v.id">
                <button type="button" class="card-variant-chip" @click.stop.prevent="$store.cart.add({{ $product->id }}, v.id); showVariants = false" x-text="v.name"></button>
              </template>
            </div>
          </div>
        @endif
      </div>
      <div class="card-body">
        <div class="card-cat">{{ $product->category->name }}</div>
        <div class="card-name">{{ $product->name }}</div>
        <div class="card-price">
          @if($product->currency === 'USD')
            ${{ number_format($product->price, 2) }} <small>USD</small>
          @else
            Bs {{ number_format($product->price, 2) }} <small>BOB</small>
          @endif
        </div>
      </div>
    </a>
  @empty
    <p style="color:var(--text-secondary);">No hay productos en esta categoría todavía.</p>
  @endforelse
</div>

<div class="pagination-links">
  {{ $products->links('partials.pagination') }}
</div>
