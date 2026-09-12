@php
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

  if (!empty($data['marca'])) {
    $query->whereRaw('LOWER(brand) = ?', [mb_strtolower($data['marca'])]);
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

  // Este bloque siempre muestra una vista previa acotada (ver $limite) --
  // si tiene categoría y/o marca, el link manda a la tienda real filtrada
  // igual (con paginación de verdad) en vez de duplicar esa lógica acá.
  $verTodoUrl = (!empty($data['categoria']) || !empty($data['marca']))
    ? route('shop', array_filter(['category' => $data['categoria'] ?? null, 'marca' => $data['marca'] ?? null]))
    : null;
@endphp
<div class="wrap cms-block">
  @if(!empty($data['eyebrow']) || !empty($data['subtitulo']))
    <div class="cms-productos-head" data-reveal>
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
    <h2 class="cms-titulo cms-titulo-mediano" style="margin-bottom:20px;" data-reveal>{{ $data['titulo'] }}@if(!empty($data['titulo_destacado'])) <em class="cms-hero-em">{{ $data['titulo_destacado'] }}</em>@endif</h2>
  @endif
  <div class="product-grid" data-reveal-group>
    @foreach($productos as $product)
      @include('partials.product-card', ['product' => $product])
    @endforeach
  </div>
  @if($verTodoUrl)
    <div style="text-align:center;margin-top:32px;">
      <a href="{{ $verTodoUrl }}" class="btn-outline-gold">Ver todo{{ !empty($data['marca']) ? ' '.$data['marca'] : '' }} →</a>
    </div>
  @endif
</div>
