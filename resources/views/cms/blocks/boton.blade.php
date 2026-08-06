<div class="wrap cms-block" style="text-align:center;">
  @if(!empty($data['texto']) && !empty($data['url']))
    <a href="{{ $data['url'] }}" class="btn {{ ($data['estilo'] ?? 'primario') === 'primario' ? 'btn-primary' : '' }}">{{ $data['texto'] }}</a>
  @endif
</div>
