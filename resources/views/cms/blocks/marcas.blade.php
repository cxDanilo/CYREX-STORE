@php($items = $data['items'] ?? [])
@php($logoSize = (int) ($data['logo_size'] ?? 64))
@php($speed = (int) ($data['speed'] ?? 28))
@php
  // Si el nombre de la marca coincide con una Brand que ya tiene su
  // propia página publicada (/marcas/{slug}), el click va ahí primero
  // en vez del link que el admin haya tipeado a mano (ej.
  // /tienda?marca=X) — la idea es "conocé la marca" antes de "comprá
  // ya". Si no hay página para esa marca, se respeta el link de
  // siempre tal cual, sin romper nada de lo ya cargado.
  $publishedBrandSlugs = \App\Models\Brand::where('is_page_published', true)->get()
    ->mapWithKeys(fn ($b) => [mb_strtolower($b->name) => $b->slug]);
  $resolveLink = function ($item) use ($publishedBrandSlugs) {
    $slug = $publishedBrandSlugs[mb_strtolower(trim($item['nombre'] ?? ''))] ?? null;

    return $slug ? route('brand.show', $slug) : ($item['link'] ?? null);
  };
@endphp
<div class="wrap cms-block cms-marcas-wrap" style="--marca-logo-size:{{ $logoSize }}px;--marca-speed:{{ $speed }}s;">
  <div class="cms-marcas-track">
    @foreach($items as $item)
      @php($link = $resolveLink($item))
      <div class="cms-marca-item">
        @if(!empty($link))
          <a href="{{ $link }}" class="cms-marca-link" aria-label="Ver productos {{ $item['nombre'] ?? '' }}">
            <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['nombre'] ?? '' }}" class="cms-marca-logo">
          </a>
        @else
          <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['nombre'] ?? '' }}" class="cms-marca-logo">
        @endif
      </div>
    @endforeach
    @foreach($items as $item)
      @php($link = $resolveLink($item))
      <div class="cms-marca-item" aria-hidden="true">
        @if(!empty($link))
          <a href="{{ $link }}" class="cms-marca-link" tabindex="-1">
            <img src="{{ $item['url'] ?? '' }}" alt="" class="cms-marca-logo">
          </a>
        @else
          <img src="{{ $item['url'] ?? '' }}" alt="" class="cms-marca-logo">
        @endif
      </div>
    @endforeach
  </div>
</div>
