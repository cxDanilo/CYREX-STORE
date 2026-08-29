@php
  $items = collect($data['items'] ?? [])->filter(fn ($item) => !empty($item['imagen']))->values();
  $paginas = $items->chunk(7)->values();
  $intervaloMs = max(0, (int) ($data['intervalo'] ?? 6)) * 1000;

  // 3 moldes de mosaico distintos (posición y forma de cada tarjeta,
  // en una grilla fija de 6 columnas x 2 filas), rotando por tanda
  // para que no todas se vean armadas con el mismo molde.
  $patrones = [
    [
      ['c' => '1 / 3', 'r' => '1 / 3', 'forma' => 'big'],
      ['c' => '3 / 5', 'r' => '1 / 2', 'forma' => 'wide'],
      ['c' => '5 / 6', 'r' => '1 / 3', 'forma' => 'tall'],
      ['c' => '6 / 7', 'r' => '1 / 2', 'forma' => 'square'],
      ['c' => '3 / 4', 'r' => '2 / 3', 'forma' => 'square'],
      ['c' => '4 / 5', 'r' => '2 / 3', 'forma' => 'square'],
      ['c' => '6 / 7', 'r' => '2 / 3', 'forma' => 'square'],
    ],
    [
      ['c' => '1 / 2', 'r' => '1 / 3', 'forma' => 'tall'],
      ['c' => '2 / 4', 'r' => '1 / 2', 'forma' => 'wide'],
      ['c' => '4 / 6', 'r' => '1 / 3', 'forma' => 'big'],
      ['c' => '6 / 7', 'r' => '1 / 2', 'forma' => 'square'],
      ['c' => '2 / 3', 'r' => '2 / 3', 'forma' => 'square'],
      ['c' => '3 / 4', 'r' => '2 / 3', 'forma' => 'square'],
      ['c' => '6 / 7', 'r' => '2 / 3', 'forma' => 'square'],
    ],
    [
      ['c' => '1 / 2', 'r' => '1 / 2', 'forma' => 'square'],
      ['c' => '2 / 3', 'r' => '1 / 2', 'forma' => 'square'],
      ['c' => '1 / 3', 'r' => '2 / 3', 'forma' => 'wide'],
      ['c' => '3 / 5', 'r' => '1 / 3', 'forma' => 'big'],
      ['c' => '5 / 6', 'r' => '1 / 3', 'forma' => 'tall'],
      ['c' => '6 / 7', 'r' => '1 / 2', 'forma' => 'square'],
      ['c' => '6 / 7', 'r' => '2 / 3', 'forma' => 'square'],
    ],
  ];
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
        @php($patron = $patrones[$pi % count($patrones)])
        <div class="cms-marcas-mosaico-page">
          @foreach($pagina as $i => $item)
            @php($slot = $patron[$i % count($patron)])
            @if(!empty($item['link']))
              <a class="cms-marcas-mosaico-tile" data-shape="{{ $slot['forma'] }}" style="grid-column:{{ $slot['c'] }};grid-row:{{ $slot['r'] }};" href="{{ $item['link'] }}">
            @else
              <div class="cms-marcas-mosaico-tile" data-shape="{{ $slot['forma'] }}" style="grid-column:{{ $slot['c'] }};grid-row:{{ $slot['r'] }};">
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
