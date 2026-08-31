@php
  // Solo categorías que realmente tienen al menos un producto activo
  // asignado — nunca queremos mostrar una sección vacía. El orden se
  // baraja con una semilla del día (no en cada visita) para que la
  // categoría cambie una vez al día, igual para todos los visitantes.
  //
  // Estas queries corrían sin caché en cada carga del home — se
  // envuelven en Cache::remember con la posición+día+límite como
  // clave (así cada bloque de la página cachea su propio resultado
  // por separado) y un TTL de 15 minutos: alcanza para no pegarle a
  // la base en cada visita, sin dejar un producto nuevo invisible
  // todo el día — coincide con que esto ya se pensó para cambiar
  // "una vez al día", no en tiempo real.
  $seed = now()->format('Ymd');
  $posicion = max(1, (int) ($data['posicion'] ?? 1));
  $limite = (int) ($data['limite'] ?? 4);

  [$categoria, $productos] = \Illuminate\Support\Facades\Cache::remember(
      "catrot.{$posicion}.{$limite}.{$seed}",
      now()->addMinutes(15),
      function () use ($seed, $posicion, $limite) {
          $categoryIds = \App\Models\Product::where('status', 'active')
              ->whereNotNull('category_id')
              ->distinct()
              ->pluck('category_id');

          $categorias = \App\Models\Category::whereIn('id', $categoryIds)
              ->get()
              ->sortBy(fn ($c) => crc32($seed.'-cat-'.$c->id))
              ->values();

          $categoria = $categorias->get($posicion - 1);

          $productos = $categoria
              ? \App\Models\Product::where('status', 'active')
                  ->where('category_id', $categoria->id)
                  ->with('variants')
                  ->latest()
                  ->take($limite)
                  ->get()
              : collect();

          return [$categoria, $productos];
      }
  );
@endphp
@if($categoria && $productos->isNotEmpty())
<div class="wrap cms-block">
  <div class="cms-catrot-head">
    <div>
      @if(!empty($data['etiqueta']))
        <div class="cms-catrot-badge">{{ $data['etiqueta'] }}</div>
      @endif
      <h2 class="cms-titulo cms-titulo-mediano">{{ $categoria->name }}</h2>
    </div>
    <a href="{{ route('shop', ['category' => $categoria->slug]) }}" class="cms-catrot-vermas">Ver más →</a>
  </div>
  <div class="product-grid">
    @foreach($productos as $product)
      <a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;">
        <div class="card-media">
          @if($product->image_thumb_url)
            <img src="{{ $product->image_thumb_url }}" alt="{{ $product->name }}" loading="lazy" style="{{ $product->is_sold_out ? 'filter:grayscale(1);' : '' }}">
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
@endif
