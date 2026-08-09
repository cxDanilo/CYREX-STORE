@php($items = $data['items'] ?? [])
<div class="wrap cms-block cms-carrusel" x-data="{ i: 0, total: {{ count($items) }} }">
  <div class="cms-carrusel-track">
    @foreach($items as $idx => $item)
      <div class="cms-carrusel-slide" x-show="i === {{ $idx }}" x-transition.opacity.duration.300ms>
        <img src="{{ $item['url'] ?? '' }}" alt="" loading="lazy">
        @if(!empty($item['texto']))
          <div class="cms-carrusel-caption">{{ $item['texto'] }}</div>
        @endif
      </div>
    @endforeach
  </div>
  @if(count($items) > 1)
    <div class="cms-carrusel-nav">
      <button type="button" class="cms-carrusel-arrow" x-on:click="i = (i - 1 + total) % total" aria-label="Anterior">←</button>
      <div class="cms-carrusel-dots">
        @foreach($items as $idx => $item)
          <button type="button" class="cms-carrusel-dot" :class="i === {{ $idx }} && 'active'" x-on:click="i = {{ $idx }}" aria-label="Ir a slide {{ $idx + 1 }}"></button>
        @endforeach
      </div>
      <button type="button" class="cms-carrusel-arrow" x-on:click="i = (i + 1) % total" aria-label="Siguiente">→</button>
    </div>
  @endif
</div>
