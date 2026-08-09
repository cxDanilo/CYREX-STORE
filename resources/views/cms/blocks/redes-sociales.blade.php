@php
  $items = $data['items'] ?? [];
  $pages = collect($items)->chunk(3)->values();
@endphp
@if($pages->isNotEmpty())
<div class="wrap cms-block">
  @if(!empty($data['titulo']))
    <h2 class="cms-titulo cms-titulo-mediano" style="text-align:center;margin-bottom:28px;">{{ $data['titulo'] }}</h2>
  @endif
  <div class="cms-social-rotator" data-interval="5000">
    @foreach($pages as $i => $page)
      <div class="cms-social-page @if($i === 0) is-active @endif">
        @foreach($page as $item)
          <a href="{{ $item['url'] ?? '#' }}" target="_blank" rel="noopener" class="cms-social-card">
            <div class="cms-social-media">
              @if(!empty($item['imagen']))
                <img src="{{ $item['imagen'] }}" alt="" loading="lazy">
              @endif
              <span class="cms-social-badge">
                @include('partials.social-icon', ['platform' => $item['plataforma'] ?? 'instagram'])
              </span>
            </div>
            @if(!empty($item['texto']))
              <div class="cms-social-caption">{{ $item['texto'] }}</div>
            @endif
          </a>
        @endforeach
      </div>
    @endforeach
  </div>
</div>
@endif
