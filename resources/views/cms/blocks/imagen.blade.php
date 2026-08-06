<figure class="wrap cms-block cms-imagen">
  @if(!empty($data['url']))
    <img src="{{ $data['url'] }}" alt="{{ $data['alt'] ?? '' }}">
  @endif
  @if(!empty($data['leyenda']))
    <figcaption>{{ $data['leyenda'] }}</figcaption>
  @endif
</figure>
