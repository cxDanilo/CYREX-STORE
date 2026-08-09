@extends('layouts.app')

@section('title', 'Tienda — Cyrex Store')

@section('content')

<div class="page-head wrap">
  <div class="breadcrumb">
    <a href="{{ route('home') }}">Inicio</a> / <a href="{{ route('shop') }}">Tienda</a>
    @if($activeCategory) / {{ $activeCategory->name }} @endif
  </div>
  <h1>{{ $activeCategory ? $activeCategory->name : 'Tienda' }}</h1>
</div>

<div class="wrap shop-layout">
  <div class="shop-main">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
      <div style="color:var(--text-secondary);font-size:14px;">
        <b style="color:var(--text-primary);">{{ $products->total() }}</b> productos
      </div>
      @if($activeCategory)
        <a href="{{ route('shop') }}" class="btn btn-sm">Ver todo el catálogo ×</a>
      @endif
    </div>

    <div class="product-grid">
      @forelse($products as $product)
        <a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;"
           x-data="{
             showVariants: false,
             hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
             variants: {{ $product->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->variant_value])->toJson() }},
             quickAdd() {
               if (this.hasVariants) { this.showVariants = !this.showVariants; }
               else { $store.cart.add({{ $product->id }}, null); }
             }
           }"
           @click.outside="showVariants = false">
          <div class="card-media">
            @if($product->image_url)
              <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
            @endif
            <button type="button" class="card-quick-add" @click.stop.prevent="quickAdd()" aria-label="Agregar {{ $product->name }} al carrito">
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
  </div>
</div>

@endsection
