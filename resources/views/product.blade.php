@extends('layouts.app')

@section('title', $product->name . ' — Cyrex Store')

@section('content')

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / <a href="{{ route('shop', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a> / {{ $product->name }}
</div>

<div class="wrap product-hero"
     x-data="{
        variant: {{ $product->variants->first()?->id ?? 'null' }},
        showBob: false,
        rate: {{ $rate }},
        basePrice: {{ $product->currency === 'USD' ? $product->price : $product->price / $rate }},
        variants: {{ $product->variants->map(fn($v) => ['id' => $v->id, 'name' => $v->variant_value, 'stock' => $v->stock])->toJson() }}
     }">
  <div class="gallery">
    <div class="gallery-main"></div>
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
      <div class="price-toggle" style="margin-bottom:14px;">
        <button @click="showBob = false" :style="!showBob ? 'color:var(--gold)' : 'color:var(--text-muted)'" style="background:none;border:none;font-family:var(--font-mono);font-size:12px;margin-right:12px;cursor:pointer;">USD</button>
        <button @click="showBob = true" :style="showBob ? 'color:var(--gold)' : 'color:var(--text-muted)'" style="background:none;border:none;font-family:var(--font-mono);font-size:12px;cursor:pointer;">BOB</button>
      </div>
      <div class="price-main" x-text="showBob ? 'Bs ' + (basePrice * rate).toFixed(2) : '$' + basePrice.toFixed(2)"></div>
      <div class="price-alt" x-text="showBob ? '≈ $' + basePrice.toFixed(2) + ' USD' : '≈ Bs ' + (basePrice * rate).toFixed(2)"></div>
    </div>

    <button class="btn-whatsapp" onclick="window.open('https://wa.me/59177947379?text=Hola, me interesa el {{ urlencode($product->name) }}', '_blank')">
      Consultar por WhatsApp
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
        <div class="card-media"></div>
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
