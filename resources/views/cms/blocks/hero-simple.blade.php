<section class="wrap cms-block cms-hero">
  @if(!empty($data['titulo']))
    <h1 class="cms-hero-title">{{ $data['titulo'] }}</h1>
  @endif
  @if(!empty($data['subtitulo']))
    <p class="cms-hero-subtitle">{{ $data['subtitulo'] }}</p>
  @endif
  @if(!empty($data['cta_label']) && !empty($data['cta_url']))
    <a href="{{ $data['cta_url'] }}" class="btn btn-primary">{{ $data['cta_label'] }}</a>
  @endif
</section>
