@extends('layouts.app')

@section('title', $combo->name . ' — Cyrex Store')

@section('content')

@php
  $comboUsd = $combo->priceInUsd($rate);
  $comboBob = $combo->priceInBob($rate);
  $individualUsd = $combo->individualTotalUsd($rate);
  $savingsUsd = max(0, $individualUsd - $comboUsd);
  $showBobFirst = $currencyMode === 'bob_only' || ($currencyMode === 'both' && $defaultCurrency === 'BOB');
  $whatsappNumber = \App\Models\Setting::get('whatsapp_number', '59177947379');
  $waText = "Hola! Me interesa este combo:\n{$combo->name}\n"
      .($showBobFirst ? 'Bs '.number_format($comboBob, 2) : '$'.number_format($comboUsd, 2))
      ."\n".route('combo.show', $combo->slug);
@endphp

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / Combo / {{ $combo->name }}
</div>

<div class="wrap product-hero" x-data="{ inCart: $store.cart.hasCombo({{ $combo->id }}) }">
  <div class="gallery">
    <div class="gallery-main">
      @if($combo->image_url)
        <img src="{{ $combo->image_url }}" alt="{{ $combo->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:20px;">
      @endif
    </div>
  </div>

  <div class="product-info">
    <div class="cat-eyebrow">Combo</div>
    <h1>{{ $combo->name }}</h1>

    <div class="price-block">
      @if($showBobFirst)
        <div class="price-main"><span class="price-text">Bs {{ number_format($comboBob, 2) }}</span></div>
        <div class="price-alt"><span class="price-text">≈ ${{ number_format($comboUsd, 2) }} USD</span></div>
      @else
        <div class="price-main"><span class="price-text">${{ number_format($comboUsd, 2) }}</span></div>
        <div class="price-alt"><span class="price-text">≈ Bs {{ number_format($comboBob, 2) }}</span></div>
      @endif
    </div>

    @if($savingsUsd >= 0.01)
      <div class="form-hint" style="margin-top:-6px;margin-bottom:16px;">
        Comprado por separado: <span style="text-decoration:line-through;">${{ number_format($individualUsd, 2) }}</span>
        — ahorras <strong style="color:var(--gold);">${{ number_format($savingsUsd, 2) }}</strong>
      </div>
    @endif

    <div class="btn-cta-row">
      <button type="button" class="btn-cta" :class="inCart && 'in-cart'"
              @click="
                if (!inCart) {
                  await $store.cart.addCombo({{ $combo->id }});
                  inCart = true;
                }
              ">
        <span x-text="inCart ? 'En el carrito ✓' : 'Agregar todo al carrito'">Agregar todo al carrito</span>
      </button>

      <a href="https://wa.me/{{ $whatsappNumber }}?text={{ urlencode($waText) }}" target="_blank" rel="noopener" class="btn-cta-whatsapp">
        @include('partials.whatsapp-icon')
        <span>Consultar</span>
      </a>
    </div>

    @if($combo->description)
      <div class="product-description">{{ $combo->description }}</div>
    @endif

    <h3 style="margin-top:24px;margin-bottom:10px;">Este combo incluye</h3>
    <table class="spec-table">
      @foreach($combo->products as $product)
        <tr>
          <td>
            <a href="{{ route('product.show', $product->slug) }}" style="color:inherit;">{{ $product->name }}</a>
          </td>
          <td class="mono" style="color:var(--text-muted);">
            @if($product->currency === 'USD')
              ${{ number_format($product->price, 2) }}
            @else
              Bs {{ number_format($product->price, 2) }}
            @endif
          </td>
        </tr>
      @endforeach
    </table>
  </div>
</div>

@endsection
