@extends('layouts.app')

@section('title', 'Tienda — Cyrex Store')

@section('content')

<div class="page-head wrap">
  <div class="breadcrumb"><a href="{{ route('home') }}">Inicio</a> / Tienda</div>
  <h1>Tienda</h1>
</div>

<div class="wrap shop-layout">
  <aside class="sidebar">
    <div class="sidebar-title">Filtrar categorías</div>
    <div class="cat-item {{ !request('category') ? 'active' : '' }}">
      <a href="{{ route('shop') }}">Todas</a>
    </div>
    @foreach($categories as $parent)
      <div class="cat-item">
        <a href="{{ route('shop', ['category' => $parent->slug]) }}">{{ $parent->name }}</a>
      </div>
      <div class="cat-children">
        @foreach($parent->children as $child)
          <a href="{{ route('shop', ['category' => $child->slug]) }}"
             class="{{ request('category') === $child->slug ? 'active' : '' }}">
            {{ $child->name }}
          </a>
        @endforeach
      </div>
    @endforeach
  </aside>

  <div class="shop-main">
    <div style="margin-bottom:20px;color:var(--text-secondary);font-size:14px;">
      <b style="color:var(--text-primary);">{{ $products->total() }}</b> productos
    </div>

    <div class="product-grid">
      @forelse($products as $product)
        <a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;">
          <div class="card-media">
            @if($product->has_variants)
              <span class="badge">Variantes</span>
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
      {{ $products->links() }}
    </div>
  </div>
</div>

@endsection
