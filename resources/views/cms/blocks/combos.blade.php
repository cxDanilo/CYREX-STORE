@php
  $rate = \App\Models\ExchangeRate::current();
  $limite = (int) ($data['limite'] ?? 8);
  $porPagina = 4;
  $combos = \App\Models\Combo::where('active', true)
      ->with('products')
      ->orderBy('sort_order')
      ->orderBy('name')
      ->take($limite)
      ->get()
      ->filter(fn ($combo) => $combo->products->count() >= 2)
      ->values();
  $paginas = $combos->chunk($porPagina);
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
  {{-- cms-social-rotator/-page: mismo mecanismo genérico que ya usa
       "Descubrí las mejores marcas" del home y "Completa tu setup" del
       armador (public/js/social-rotator.js) para ir rotando de tanda
       sola cada tantos segundos — no hace falta JS nuevo. Con 4 o menos
       combos entra todo en una sola tanda y el script no rota nada
       (pages.length <= 1), que es exactamente lo que se quiere en ese
       caso. --}}
  <div class="cms-social-rotator" data-interval="{{ (int) ($data['intervalo'] ?? 6000) }}">
    @foreach($paginas as $i => $pagina)
      <div class="cms-social-page @if($i === 0) is-active @endif">
        @foreach($pagina as $combo)
          @php
            $comboUsd = $combo->priceInUsd($rate);
            $savingsUsd = max(0, $combo->individualTotalUsd($rate) - $comboUsd);
          @endphp
          <a class="card" href="{{ route('combo.show', $combo->slug) }}" style="display:block;">
            <div class="card-media">
              @if($combo->image_thumb_url)
                <img src="{{ $combo->image_thumb_url }}" alt="{{ $combo->name }}" loading="lazy"
                     onload="this.classList.add('is-loaded')" onerror="this.classList.add('is-loaded')">
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
    @endforeach
  </div>
</div>
@endif
