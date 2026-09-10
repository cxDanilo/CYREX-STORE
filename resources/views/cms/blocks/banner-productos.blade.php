@php
  $modo = $data['modo'] ?? 'categoria';

  if ($modo === 'manual') {
    $slugs = collect($data['items'] ?? [])->pluck('producto')->filter()->values();
    $productos = \App\Models\Product::whereIn('slug', $slugs)->where('status', 'active')->with(['category', 'variants'])->get()
      ->sortBy(fn ($p) => $slugs->search($p->slug))
      ->values();
  } else {
    $query = \App\Models\Product::where('status', 'active')->with(['category', 'variants']);

    if (!empty($data['categoria'])) {
      $cat = \App\Models\Category::where('slug', $data['categoria'])->first();
      if ($cat) {
        $ids = $cat->parent_id ? [$cat->id] : $cat->children()->pluck('id')->push($cat->id);
        $isAutoPromoCategory = (int) \App\Models\Setting::get('auto_promo_category_id', '') === $cat->id;

        $query->where(function ($q) use ($ids, $isAutoPromoCategory) {
          $q->whereIn('category_id', $ids)
            ->orWhereHas('categories', fn ($q2) => $q2->whereIn('categories.id', $ids));

          if ($isAutoPromoCategory) {
            $q->orWhere(fn ($q3) => $q3->hasActiveOffer());
          }
        });
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
          @include('partials.product-card', ['product' => $product])
        @endforeach
      </div>
    @endif
  </div>
</section>
