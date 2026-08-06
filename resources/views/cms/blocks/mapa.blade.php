<div class="wrap cms-block cms-mapa">
  @if(!empty($data['direccion']))
    <iframe src="https://www.google.com/maps?q={{ urlencode($data['direccion']) }}&output=embed" loading="lazy" allowfullscreen></iframe>
  @endif
</div>
