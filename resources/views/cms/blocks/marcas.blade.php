@php($items = $data['items'] ?? [])
@php($logoSize = (int) ($data['logo_size'] ?? 64))
<div class="wrap cms-block cms-marcas-wrap" style="--marca-logo-size:{{ $logoSize }}px;">
  <div class="cms-marcas-track">
    @foreach($items as $item)
      <div class="cms-marca-item">
        <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['nombre'] ?? '' }}" class="cms-marca-logo">
      </div>
    @endforeach
    @foreach($items as $item)
      <div class="cms-marca-item" aria-hidden="true">
        <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['nombre'] ?? '' }}" class="cms-marca-logo">
      </div>
    @endforeach
  </div>
</div>
