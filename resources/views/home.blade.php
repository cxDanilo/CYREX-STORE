@extends('layouts.app')

@section('title', 'Cyrex Store — Componentes y periféricos gamer en Bolivia')

@section('styles')
<style>
  .hero{min-height:70vh;display:flex;flex-direction:column;justify-content:center;padding:80px 0;border-bottom:1px solid var(--border);}
  .hero h1{font-family:var(--font-display);font-weight:700;font-size:clamp(36px,6vw,72px);line-height:1.02;letter-spacing:-0.03em;max-width:800px;margin-bottom:20px;}
  .hero h1 em{font-style:normal;color:var(--gold);}
  .hero p{font-size:18px;color:var(--text-secondary);max-width:480px;margin-bottom:32px;}
  .hero-cta{display:inline-block;background:var(--gold);color:#0A0A0B;font-weight:600;padding:14px 28px;border-radius:100px;}
  .cat-strip{padding:40px 0;display:flex;gap:12px;overflow-x:auto;border-bottom:1px solid var(--border);}
  .cat-chip{flex-shrink:0;font-size:14px;padding:10px 20px;border-radius:100px;border:1px solid var(--border);color:var(--text-secondary);white-space:nowrap;}
  .cat-chip:hover{border-color:var(--gold);color:var(--gold);}
  .featured{padding:60px 0 100px;}
  .featured h2{font-family:var(--font-display);font-weight:600;font-size:24px;margin-bottom:24px;}
</style>
@endsection

@section('content')

<section class="hero wrap">
  <div class="eyebrow" style="font-family:var(--font-mono);color:var(--gold);font-size:13px;margin-bottom:16px;">Cyrex Store</div>
  <h1>No es una tienda.<br>Es tu próximo setup <em>cobrando vida</em>.</h1>
  <p>Componentes y periféricos gamer en Bolivia. Stock real, precio claro, gente que entiende de esto.</p>
  <a class="hero-cta" href="{{ route('shop') }}">Explorar la tienda</a>
</section>

<section class="cat-strip wrap">
  @foreach($categories as $cat)
    <a class="cat-chip" href="{{ route('shop', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
  @endforeach
</section>

<section class="featured wrap">
  <h2>Recién llegados</h2>
  <div class="product-grid">
    @foreach($featured as $product)
      <a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;">
        <div class="card-media">
          @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
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
    @endforeach
  </div>
</section>

@endsection
{{-- prueba flujo git deploy: 2026-08-05 --}}
