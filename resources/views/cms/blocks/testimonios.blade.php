<div class="wrap cms-block cms-testimonios">
  @foreach($data['items'] ?? [] as $item)
    <div class="cms-testimonio">
      <p class="cms-testimonio-texto">"{{ $item['texto'] ?? '' }}"</p>
      <div class="cms-testimonio-nombre">{{ $item['nombre'] ?? '' }}</div>
    </div>
  @endforeach
</div>
