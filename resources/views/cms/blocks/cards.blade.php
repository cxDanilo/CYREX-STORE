<div class="wrap cms-block cms-cards">
  @foreach($data['items'] ?? [] as $item)
    <div class="cms-card">
      <h4 class="cms-card-title">{{ $item['titulo'] ?? '' }}</h4>
      <p class="cms-card-text">{{ $item['texto'] ?? '' }}</p>
    </div>
  @endforeach
</div>
