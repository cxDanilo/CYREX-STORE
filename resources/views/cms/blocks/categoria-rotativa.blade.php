@php
  // Solo categorías que realmente tienen al menos un producto activo
  // asignado — nunca queremos mostrar una sección vacía. El orden se
  // baraja con una semilla del día (no en cada visita) para que la
  // categoría cambie una vez al día, igual para todos los visitantes.
  $categoryIds = \App\Models\Product::where('status', 'active')
      ->whereNotNull('category_id')
      ->distinct()
      ->pluck('category_id');

  $seed = now()->format('Ymd');
  $categorias = \App\Models\Category::whereIn('id', $categoryIds)
      ->get()
      ->sortBy(fn ($c) => crc32($seed.'-cat-'.$c->id))
      ->values();

  $posicion = max(1, (int) ($data['posicion'] ?? 1));
  $categoria = $categorias->get($posicion - 1);

  $productos = $categoria
      ? \App\Models\Product::where('status', 'active')
          ->where('category_id', $categoria->id)
          ->latest()
          ->take((int) ($data['limite'] ?? 4))
          ->get()
      : collect();
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
    @endforeach
  </div>
</div>
@endif
