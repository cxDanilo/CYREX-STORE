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

    <div class="product-grid">
      @forelse($products as $product)
        <a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;">
          <div class="card-media">
            @if($product->image_url)
              <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
            @endif
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
      {{ $products->links('partials.pagination') }}
    </div>
  </div>
</div>

@endsection
