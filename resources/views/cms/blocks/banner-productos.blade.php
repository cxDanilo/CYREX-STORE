@php
  $modo = $data['modo'] ?? 'categoria';

  if ($modo === 'manual') {
    $slugs = collect($data['items'] ?? [])->pluck('producto')->filter()->values();
    $productos = \App\Models\Product::whereIn('slug', $slugs)->where('status', 'active')->with(['category'])->get()
      ->sortBy(fn ($p) => $slugs->search($p->slug))
      ->values();
  } else {
    $query = \App\Models\Product::where('status', 'active')->with(['category']);

    if (!empty($data['categoria'])) {
      $cat = \App\Models\Category::where('slug', $data['categoria'])->first();
      if ($cat) {
        $ids = $cat->parent_id ? [$cat->id] : $cat->children()->pluck('id')->push($cat->id);
        $query->whereIn('category_id', $ids);
      }
    }

    $limite = (int) ($data['limite'] ?? 3);
    $productos = $query->orderByDesc('created_at')->take($limite)->get();
  }

  $cardAlpha = max(0, min(100, (int) ($data['card_opacidad'] ?? 55))) / 100;
@endphp
<section class="cms-banner-productos" style="background-image:url('{{ $data['imagen_fondo'] ?? '' }}')">
  <div class="wrap cms-banner-productos-inner">
    <div class="cms-banner-productos-text">
      @if(!empty($data['eyebrow']))
        <div class="cms-hero-eyebrow">{{ $data['eyebrow'] }}</div>
      @endif
      @if(!empty($data['titulo']))
        <h2 class="cms-hero-title">{!! nl2br(e($data['titulo'])) !!}</h2>
      @endif
      @if(!empty($data['subtitulo']))
        <p class="cms-hero-subtitle">{{ $data['subtitulo'] }}</p>
      @endif
      @if(!empty($data['boton_texto']) && !empty($data['boton_url']))
        <a href="{{ $data['boton_url'] }}" class="btn btn-primary">{{ $data['boton_texto'] }}</a>
      @endif
    </div>
    @if($productos->isNotEmpty())
      <div class="cms-banner-productos-grid" style="--banner-card-alpha:{{ $cardAlpha }};">
        @foreach($productos as $product)
          <a class="card" href="{{ route('product.show', $product->slug) }}">
            <div class="card-media">
              @if($product->image_thumb_url)
                <img src="{{ $product->image_thumb_url }}" alt="{{ $product->name }}" loading="lazy" style="{{ $product->is_sold_out ? 'filter:grayscale(1);' : '' }}"
                     onload="this.classList.add('is-loaded')" onerror="this.classList.add('is-loaded')">
              @endif
              <div class="card-badges">
                @if($product->is_sold_out)
                  <span class="card-badge-agotado">Agotado</span>
                @endif
                @if($promo = $product->activePromotion($cardActivePromotion ?? null))
                  <span class="card-badge-promo">{{ $promo->discount_label ?: 'Oferta' }}</span>
                @endif
                @if($product->hasActiveOffer())
                  <span class="card-badge-promo">-{{ $product->offerDiscountPercent() }}%</span>
                @endif
              </div>
            </div>
            <div class="card-body">
              <div class="card-cat">{{ $product->category->name }}</div>
              <div class="card-name">{{ $product->name }}</div>
              @if($product->hasActiveOffer())
                <div class="card-price-original">
                  @if($product->currency === 'USD')
                    ${{ number_format($product->price, 2) }}
                  @else
                    Bs {{ number_format($product->price, 2) }}
                  @endif
                </div>
              @endif
              <div class="card-price">
                @if($product->currency === 'USD')
                  ${{ number_format($product->effectivePrice(), 2) }} <small>USD</small>
                @else
                  Bs {{ number_format($product->effectivePrice(), 2) }} <small>BOB</small>
                @endif
              </div>
              @if($product->hasActiveOffer())
                <div class="card-offer-countdown" x-text="$store.offer.remaining"></div>
              @endif
            </div>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>
