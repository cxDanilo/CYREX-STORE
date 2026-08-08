@php
  $videoUrl = trim($data['video_url'] ?? '');
  $embedUrl = $videoUrl ? \App\Support\VideoEmbed::backgroundEmbedUrl($videoUrl) : null;
  $isDirectFile = $videoUrl && ! $embedUrl;
@endphp
<section class="cms-hero-video @if(!empty($data['poster_url'])) cms-hero-video-has-poster @endif">
  <div class="cms-hero-video-media">
    @if($embedUrl)
      <iframe class="cms-hero-video-iframe" src="{{ $embedUrl }}" allow="autoplay; encrypted-media" tabindex="-1" aria-hidden="true"></iframe>
    @elseif($isDirectFile)
      <video class="cms-hero-video-native" src="{{ $videoUrl }}" @if(!empty($data['poster_url'])) poster="{{ $data['poster_url'] }}" @endif autoplay muted loop playsinline></video>
    @endif
    @if(!empty($data['poster_url']))
      <img class="cms-hero-video-poster" src="{{ $data['poster_url'] }}" alt="">
    @endif
    <div class="cms-hero-video-overlay"></div>
  </div>
  <div class="wrap cms-hero-video-content">
    @if(!empty($data['titulo']))
      <h1 class="cms-hero-video-title">{!! nl2br(e($data['titulo'])) !!}</h1>
    @endif
    @if((!empty($data['boton1_texto']) && !empty($data['boton1_url'])) || (!empty($data['boton2_texto']) && !empty($data['boton2_url'])))
      <div class="cms-hero-video-buttons">
        @if(!empty($data['boton1_texto']) && !empty($data['boton1_url']))
          <a href="{{ $data['boton1_url'] }}" class="btn btn-primary">{{ $data['boton1_texto'] }}</a>
        @endif
        @if(!empty($data['boton2_texto']) && !empty($data['boton2_url']))
          <a href="{{ $data['boton2_url'] }}" class="btn btn-primary">{{ $data['boton2_texto'] }}</a>
        @endif
      </div>
    @endif
  </div>
</section>
