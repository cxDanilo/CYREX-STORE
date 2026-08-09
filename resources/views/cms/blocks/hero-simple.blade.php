<section class="wrap cms-block cms-hero @if(($data['tamano'] ?? 'estandar') === 'grande') cms-hero-grande @endif">
  @if(!empty($data['eyebrow']))
    <div class="cms-hero-eyebrow">{{ $data['eyebrow'] }}</div>
  @endif
  @if(!empty($data['titulo']))
    <h1 class="cms-hero-title">{!! nl2br(e($data['titulo'])) !!}@if(!empty($data['titulo_destacado'])) <em class="cms-hero-em">{{ $data['titulo_destacado'] }}</em>@endif</h1>
  @endif
  @if(!empty($data['subtitulo']))
    <p class="cms-hero-subtitle">{{ $data['subtitulo'] }}</p>
  @endif
  @if((!empty($data['cta_label']) && !empty($data['cta_url'])) || (!empty($data['cta2_label']) && !empty($data['cta2_url'])))
    <div class="cms-hero-buttons">
      @if(!empty($data['cta_label']) && !empty($data['cta_url']))
        <a href="{{ $data['cta_url'] }}" class="btn btn-primary">{{ $data['cta_label'] }}</a>
      @endif
      @if(!empty($data['cta2_label']) && !empty($data['cta2_url']))
        <a href="{{ $data['cta2_url'] }}" class="btn">{{ $data['cta2_label'] }}</a>
      @endif
    </div>
  @endif
</section>
