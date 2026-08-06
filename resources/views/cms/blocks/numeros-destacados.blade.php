<div class="wrap cms-block cms-stats">
  @foreach($data['items'] ?? [] as $item)
    <div class="cms-stat">
      <div class="cms-stat-number">{{ $item['numero'] ?? '' }}</div>
      <div class="cms-stat-label">{{ $item['etiqueta'] ?? '' }}</div>
    </div>
  @endforeach
</div>
