<div class="wrap cms-block cms-cta">
  @if(!empty($data['texto']))
    <div class="cms-cta-texto">{{ $data['texto'] }}</div>
  @endif
  @if(!empty($data['boton_label']) && !empty($data['boton_url']))
    <a href="{{ $data['boton_url'] }}" class="btn btn-primary">{{ $data['boton_label'] }}</a>
  @endif
</div>
