@php
  $query = \App\Models\Product::where('status', 'active')->with(['category', 'variants']);

  if (!empty($data['categoria'])) {
    $cat = \App\Models\Category::where('slug', $data['categoria'])->first();
    if ($cat) {
      $ids = $cat->parent_id ? [$cat->id] : $cat->children()->pluck('id')->push($cat->id);
      $query->whereIn('category_id', $ids);
    }
  }

  $limite = (int) ($data['limite'] ?? 4);

  if (($data['orden'] ?? 'recientes') === 'aleatorio_diario') {
    // Orden barajado en PHP (no en SQL) para que funcione igual en
    // cualquier motor de base de datos — MySQL en el servidor, SQLite en
    // local. Semilla = la fecha de hoy: mismo orden para todos los
    // visitantes durante el día, y cambia recién al pasar la medianoche.
    $seed = now()->format('Ymd');
    $productos = $query->get()
        ->sortBy(fn ($p) => crc32($seed.'-'.$p->id))
        ->take($limite)
        ->values();
  } else {
    $productos = $query->orderByDesc('created_at')->take($limite)->get();
  }
@endphp
<div class="wrap cms-block">
  @if(!empty($data['eyebrow']) || !empty($data['subtitulo']))
    <div class="cms-productos-head">
      @if(!empty($data['eyebrow']))
        <div class="cms-productos-eyebrow">{{ $data['eyebrow'] }}</div>
      @endif
      @if(!empty($data['titulo']))
        <h2 class="cms-titulo cms-titulo-grande">{{ $data['titulo'] }}@if(!empty($data['titulo_destacado'])) <em class="cms-hero-em">{{ $data['titulo_destacado'] }}</em>@endif</h2>
      @endif
      @if(!empty($data['subtitulo']))
        <p class="cms-productos-subtitulo">{{ $data['subtitulo'] }}</p>
      @endif
    </div>
  @elseif(!empty($data['titulo']))
    <h2 class="cms-titulo cms-titulo-mediano" style="margin-bottom:20px;">{{ $data['titulo'] }}@if(!empty($data['titulo_destacado'])) <em class="cms-hero-em">{{ $data['titulo_destacado'] }}</em>@endif</h2>
  @endif
  <div class="product-grid">
    @foreach($productos as $product)
      <a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;">
        <div class="card-media">
          @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" style="{{ $product->is_sold_out ? 'filter:grayscale(1);' : '' }}">
          @endif
          <div class="card-badges">
            @if($product->is_sold_out)
              <span class="card-badge-agotado">Agotado</span>
            @endif
            @if($promo = $product->activePromotion($cardActivePromotion ?? null))
              <span class="card-badge-promo">{{ $promo->discount_label ?: 'Oferta' }}</span>
            @endif
          </div>
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
</div>
