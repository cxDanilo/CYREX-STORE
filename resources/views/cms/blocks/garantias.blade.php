<div class="wrap cms-block cms-garantias">
  @foreach($data['items'] ?? [] as $item)
    <div class="cms-garantia">
      <span class="cms-garantia-icon">@include('partials.trust-icon', ['icon' => $item['icono'] ?? null])</span>
      <span>{{ $item['texto'] ?? '' }}</span>
    </div>
  @endforeach
</div>
