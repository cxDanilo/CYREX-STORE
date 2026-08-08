@extends('layouts.app')

@section('title', 'Tienda — Cyrex Store')

@section('content')

<div class="page-head wrap">
  <div class="breadcrumb"><a href="{{ route('home') }}">Inicio</a> / Tienda</div>
  <h1>Tienda</h1>
</div>

<div class="wrap shop-layout">
  <div class="shop-main">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
      <div style="color:var(--text-secondary);font-size:14px;">
        <b style="color:var(--text-primary);">{{ $products->total() }}</b> productos
      </div>
      @if(request('category'))
        <a href="{{ route('shop') }}" class="btn btn-sm">Quitar filtro ×</a>
      @endif
    </div>

    <div class="product-grid" x-data="{}">
      @forelse($products as $product)
        <a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;">
          <div class="card-media">
            @if($product->image_url)
              <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
            @endif
            @if(!$product->has_variants)
              <button type="button" class="card-quick-add" @click.stop.prevent="$store.cart.add({{ $product->id }}, null)" aria-label="Agregar {{ $product->name }} al carrito">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.4 12.4a2 2 0 0 0 2 1.6h7.2a2 2 0 0 0 2-1.6L20 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.3" fill="currentColor"/><circle cx="17" cy="20" r="1.3" fill="currentColor"/></svg>
              </button>
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
