@php($items = $data['items'] ?? [])
<div class="wrap cms-block cms-marcas-wrap">
  <div class="cms-marcas-track">
    @foreach($items as $item)
      <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['nombre'] ?? '' }}" class="cms-marca-logo">
    @endforeach
    @foreach($items as $item)
      <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['nombre'] ?? '' }}" class="cms-marca-logo" aria-hidden="true">
    @endforeach
  </div>
</div>
