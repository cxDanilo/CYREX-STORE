@php($items = $data['items'] ?? [])
@php($logoSize = (int) ($data['logo_size'] ?? 64))
@php($speed = (int) ($data['speed'] ?? 28))
<div class="wrap cms-block cms-marcas-wrap" style="--marca-logo-size:{{ $logoSize }}px;--marca-speed:{{ $speed }}s;">
  <div class="cms-marcas-track">
    @foreach($items as $item)
      <div class="cms-marca-item">
        @if(!empty($item['link']))
          <a href="{{ $item['link'] }}" class="cms-marca-link" aria-label="Ver productos {{ $item['nombre'] ?? '' }}">
            <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['nombre'] ?? '' }}" class="cms-marca-logo">
          </a>
        @else
          <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['nombre'] ?? '' }}" class="cms-marca-logo">
        @endif
      </div>
    @endforeach
    @foreach($items as $item)
      <div class="cms-marca-item" aria-hidden="true">
        @if(!empty($item['link']))
          <a href="{{ $item['link'] }}" class="cms-marca-link" tabindex="-1">
            <img src="{{ $item['url'] ?? '' }}" alt="" class="cms-marca-logo">
          </a>
        @else
          <img src="{{ $item['url'] ?? '' }}" alt="" class="cms-marca-logo">
        @endif
      </div>
    @endforeach
  </div>
</div>
