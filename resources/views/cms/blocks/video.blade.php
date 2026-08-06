@php($embedUrl = \App\Support\VideoEmbed::embedUrl($data['url'] ?? ''))
<div class="wrap cms-block cms-video">
  @if(!empty($data['titulo']))
    <h3 class="cms-titulo cms-titulo-chico" style="margin-bottom:16px;">{{ $data['titulo'] }}</h3>
  @endif
  @if($embedUrl)
    <div class="cms-video-frame">
      <iframe src="{{ $embedUrl }}" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>
    </div>
  @endif
</div>
