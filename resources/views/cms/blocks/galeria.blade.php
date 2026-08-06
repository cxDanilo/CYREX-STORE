<div class="wrap cms-block cms-galeria">
  @foreach($data['items'] ?? [] as $item)
    <div class="cms-galeria-item">
      <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['alt'] ?? '' }}">
    </div>
  @endforeach
</div>
