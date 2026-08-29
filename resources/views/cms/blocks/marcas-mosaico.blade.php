@php
  $items = collect($data['items'] ?? [])->filter(fn ($item) => !empty($item['imagen']))->values();
  $paginas = $items->chunk(7)->values();
  $ciclo = ['big', 'wide', 'tall', 'square', 'square', 'square', 'square'];
  $intervaloMs = max(0, (int) ($data['intervalo'] ?? 6)) * 1000;
@endphp
<div class="wrap cms-block cms-marcas-mosaico" data-interval="{{ $intervaloMs }}">
  @if(!empty($data['titulo']))
    <h2 class="cms-titulo cms-titulo-mediano" style="margin-bottom:20px;">{{ $data['titulo'] }}</h2>
  @endif
  @if($paginas->isEmpty())
    <p style="color:var(--text-muted);font-size:13px;">Todavía no hay marcas cargadas — agregalas desde el panel de la derecha.</p>
  @else
  <div class="cms-marcas-mosaico-viewport">
    <div class="cms-marcas-mosaico-track">
      @foreach($paginas as $pi => $pagina)
        @php
          // Cada tanda arranca en un punto distinto del ciclo de formas,
          // para que no todas se vean armadas con el mismo molde
          // (grande siempre arriba a la izquierda, etc).
          $offset = ($pi * 2) % count($ciclo);
          $formas = array_merge(array_slice($ciclo, $offset), array_slice($ciclo, 0, $offset));
        @endphp
        <div class="cms-marcas-mosaico-page">
          @foreach($pagina as $i => $item)
            @php($forma = $formas[$i % count($formas)])
            @if(!empty($item['link']))
              <a class="cms-marcas-mosaico-tile" data-shape="{{ $forma }}" href="{{ $item['link'] }}">
            @else
              <div class="cms-marcas-mosaico-tile" data-shape="{{ $forma }}">
            @endif
              <img src="{{ $item['imagen'] }}" alt="{{ $item['nombre'] ?? '' }}" loading="lazy">
              @if(!empty($item['nombre']))
                <span class="cms-marcas-mosaico-tile-label">{{ $item['nombre'] }}</span>
              @endif
            @if(!empty($item['link']))
              </a>
            @else
              </div>
            @endif
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
  @if($paginas->count() > 1)
    <div class="cms-marcas-mosaico-nav">
      <button type="button" class="cms-marcas-mosaico-arrow cms-marcas-mosaico-arrow-left" aria-label="Marcas anteriores">‹</button>
      <div class="cms-marcas-mosaico-dots">
        @foreach($paginas as $pi => $pagina)
          <button type="button" class="cms-marcas-mosaico-dot @if($pi === 0) is-active @endif" aria-label="Tanda {{ $pi + 1 }}"></button>
        @endforeach
      </div>
      <button type="button" class="cms-marcas-mosaico-arrow cms-marcas-mosaico-arrow-right" aria-label="Más marcas">›</button>
    </div>
  @endif
  @endif
</div>
