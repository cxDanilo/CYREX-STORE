@php
  $query = \App\Models\Product::where('status', 'active')->with('category');

  if (!empty($data['categoria'])) {
    $cat = \App\Models\Category::where('slug', $data['categoria'])->first();
    if ($cat) {
      $ids = $cat->parent_id ? [$cat->id] : $cat->children()->pluck('id')->push($cat->id);
      $query->whereIn('category_id', $ids);
    }
  }

  $productos = $query->orderByDesc('created_at')->take((int) ($data['limite'] ?? 4))->get();
@endphp
<div class="wrap cms-block">
  @if(!empty($data['titulo']))
    <h2 class="cms-titulo cms-titulo-mediano" style="margin-bottom:20px;">{{ $data['titulo'] }}</h2>
  @endif
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
