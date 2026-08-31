@php
  $rate = \App\Models\ExchangeRate::current();
  $limite = (int) ($data['limite'] ?? 4);
  $combos = \App\Models\Combo::where('active', true)
      ->with('products')
      ->orderBy('sort_order')
      ->orderBy('name')
      ->take($limite)
      ->get()
      ->filter(fn ($combo) => $combo->products->count() >= 2);
@endphp
@if($combos->isNotEmpty())
<div class="wrap cms-block">
  @if(!empty($data['titulo']) || !empty($data['subtitulo']))
    <div class="cms-productos-head">
      @if(!empty($data['titulo']))
        <h2 class="cms-titulo cms-titulo-grande">{{ $data['titulo'] }}@if(!empty($data['titulo_destacado'])) <em class="cms-hero-em">{{ $data['titulo_destacado'] }}</em>@endif</h2>
      @endif
      @if(!empty($data['subtitulo']))
        <p class="cms-productos-subtitulo">{{ $data['subtitulo'] }}</p>
      @endif
    </div>
  @endif
  <div class="product-grid">
    @foreach($combos as $combo)
      @php
        $comboUsd = $combo->priceInUsd($rate);
        $savingsUsd = max(0, $combo->individualTotalUsd($rate) - $comboUsd);
      @endphp
      <a class="card" href="{{ route('combo.show', $combo->slug) }}" style="display:block;">
        <div class="card-media">
          @if($combo->image_thumb_url)
            <img src="{{ $combo->image_thumb_url }}" alt="{{ $combo->name }}" loading="lazy">
          @endif
          <div class="card-badges">
            <span class="card-badge-promo">Combo</span>
          </div>
        </div>
        <div class="card-body">
          <div class="card-cat">{{ $combo->products->count() }} productos incluidos</div>
          <div class="card-name">{{ $combo->name }}</div>
          <div class="card-price">
            @if($combo->currency === 'USD')
              ${{ number_format($combo->price, 2) }} <small>USD</small>
            @else
              Bs {{ number_format($combo->price, 2) }} <small>BOB</small>
            @endif
          </div>
          @if($savingsUsd >= 0.01)
            <div style="font-size:12px;color:var(--gold);margin-top:2px;">Ahorras ${{ number_format($savingsUsd, 2) }}</div>
          @endif
        </div>
      </a>
    @endforeach
  </div>
</div>
@endif
