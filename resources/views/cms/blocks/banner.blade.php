<div class="wrap cms-block">
  <div class="cms-banner" style="background-image:url('{{ $data['imagen_url'] ?? '' }}')">
    <div class="cms-banner-overlay">
      @if(!empty($data['titulo']))
        <h3 class="cms-banner-title">{{ $data['titulo'] }}</h3>
      @endif
      @if(!empty($data['cta_label']) && !empty($data['cta_url']))
        <a href="{{ $data['cta_url'] }}" class="btn btn-primary">{{ $data['cta_label'] }}</a>
      @endif
    </div>
  </div>
</div>
