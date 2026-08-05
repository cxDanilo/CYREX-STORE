@extends('layouts.app')

@section('title', $product->name . ' — Cyrex Store')

@section('content')

@php
  $showBobInitial = $currencyMode === 'bob_only' || ($currencyMode === 'both' && $defaultCurrency === 'BOB');
  $basePriceUsd = $product->currency === 'USD' ? (float) $product->price : (float) $product->price / $rate;
  $priceMainInitial = $showBobInitial
      ? 'Bs '.number_format($basePriceUsd * $rate, 2)
      : '$'.number_format($basePriceUsd, 2);
  $priceAltInitial = $showBobInitial
      ? '≈ $'.number_format($basePriceUsd, 2).' USD'
      : '≈ Bs '.number_format($basePriceUsd * $rate, 2);
@endphp

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / <a href="{{ route('shop', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a> / {{ $product->name }}
</div>

<div class="wrap product-hero"
     x-data="{
        variant: {{ $product->variants->first()?->id ?? 'null' }},
        showBob: {{ $currencyMode === 'bob_only' || ($currencyMode === 'both' && $defaultCurrency === 'BOB') ? 'true' : 'false' }},
        toggled: false,
        rate: {{ $rate }},
        basePrice: {{ $product->currency === 'USD' ? $product->price : $product->price / $rate }},
        variants: {{ $product->variants->map(fn($v) => ['id' => $v->id, 'name' => $v->variant_value, 'stock' => $v->stock])->toJson() }}
     }">
  <div class="gallery">
    <div class="gallery-main">
      @if($product->image_url)
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:20px;">
      @endif
    </div>
  </div>

  <div class="product-info">
    <div class="cat-eyebrow">{{ $product->category->name }}</div>
    <h1>{{ $product->name }}</h1>

    @if($product->has_variants && $product->variants->count())
      <div class="variant-options">
        @foreach($product->variants as $v)
          <div class="variant-swatch"
               :class="variant === {{ $v->id }} ? 'selected' : ''"
               @click="variant = {{ $v->id }}">
            {{ $v->variant_value }}
          </div>
        @endforeach
      </div>
    @endif

    <div class="price-block">
      @if($currencyMode === 'both')
        <div class="currency-toggle" style="margin-bottom:14px;">
          <div class="toggle-thumb" :class="toggled ? (showBob ? 'to-bob' : 'to-usd') : (showBob ? 'at-bob' : 'at-usd')"></div>
          <button type="button" @click="toggled = true; showBob = false" :class="!showBob && 'active'">USD</button>
          <button type="button" @click="toggled = true; showBob = true" :class="showBob && 'active'">BOB</button>
        </div>
      @endif
      <div class="price-main" x-text="showBob ? 'Bs ' + (basePrice * rate).toFixed(2) : '$' + basePrice.toFixed(2)"
           x-effect="showBob; if (toggled) { $el.classList.remove('price-flash'); void $el.offsetWidth; $el.classList.add('price-flash'); }">{{ $priceMainInitial }}</div>
      @if($currencyMode === 'both')
        <div class="price-alt" x-text="showBob ? '≈ $' + basePrice.toFixed(2) + ' USD' : '≈ Bs ' + (basePrice * rate).toFixed(2)"
             x-effect="showBob; if (toggled) { $el.classList.remove('price-flash'); void $el.offsetWidth; $el.classList.add('price-flash'); }">{{ $priceAltInitial }}</div>
      @endif
    </div>

    <button type="button" class="btn-cta"
            :class="$store.cart.has({{ $product->id }}, variant) && 'in-cart'"
            @click="$store.cart.has({{ $product->id }}, variant) ? $store.cart.remove({{ $product->id }} + ':' + (variant ?? '')) : $store.cart.add({{ $product->id }}, variant)">
      <span x-text="$store.cart.has({{ $product->id }}, variant) ? 'En el carrito ✓' : 'Agregar al carrito'">Agregar al carrito</span>
    </button>

    @if($product->description)
      <p style="color:var(--text-secondary);font-size:15px;line-height:1.7;margin-bottom:24px;">{{ $product->description }}</p>
    @endif

    @if($product->specs)
      <table class="spec-table">
        @foreach($product->specs as $key => $value)
          <tr><td>{{ $key }}</td><td>{{ $value }}</td></tr>
        @endforeach
      </table>
    @endif
  </div>
</div>

@if($related->count())
<div class="wrap related">
  <h2>También te puede interesar</h2>
  <div class="product-grid">
    @foreach($related as $r)
      <a class="card" href="{{ route('product.show', $r->slug) }}" style="display:block;">
        <div class="card-media">
          @if($r->image_url)
            <img src="{{ $r->image_url }}" alt="{{ $r->name }}">
          @endif
        </div>
        <div class="card-body">
          <div class="card-cat">{{ $r->category->name }}</div>
          <div class="card-name">{{ $r->name }}</div>
          <div class="card-price">
            @if($r->currency === 'USD')
              ${{ number_format($r->price, 2) }}
            @else
              Bs {{ number_format($r->price, 2) }}
            @endif
          </div>
        </div>
      </a>
    @endforeach
  </div>
</div>
@endif

@endsection
