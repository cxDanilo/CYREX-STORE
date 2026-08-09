@php
  $items = $data['items'] ?? [];
  $conMapa = collect($items)->filter(fn ($s) => empty($s['proximamente']) && !empty($s['direccion']))->values();
  $whatsappGeneral = \App\Models\Setting::get('whatsapp_number', '59177947379');
@endphp
<div class="wrap cms-block cms-sucursales" id="sucursales">
  <div class="cms-sucursales-list">
    @foreach($items as $s)
      @if(!empty($s['proximamente']))
        <div class="cms-sucursal-row cms-sucursal-proximamente">
          <span class="cms-sucursal-icon">@include('partials.pin-icon')</span>
          <div class="cms-sucursal-info">
            <h3>{{ $s['nombre'] ?? '' }} está cargando…</h3>
            <p>{{ $s['mensaje_proximamente'] ?: 'Una nueva sucursal está en camino.' }}</p>
          </div>
        </div>
      @else
        <div class="cms-sucursal-row">
          <span class="cms-sucursal-icon">@include('partials.pin-icon')</span>
          <div class="cms-sucursal-info">
            <h3>{{ $s['nombre'] ?? '' }}</h3>
            <p>{{ $s['ciudad'] ?? '' }}</p>
          </div>
          @if(!empty($s['asesores']))
            <div class="cms-sucursal-asesores">
              @foreach($s['asesores'] as $a)
                @continue(empty($a['nombre']))
                <div class="cms-asesor">
                  <div class="cms-asesor-info">
                    <strong>{{ $a['nombre'] }}</strong>
                    @if(!empty($a['cargo']))<span>{{ $a['cargo'] }}</span>@endif
                  </div>
                  <a class="btn btn-sm btn-primary" href="https://wa.me/{{ preg_replace('/\D/', '', $a['whatsapp'] ?: $whatsappGeneral) }}" target="_blank" rel="noopener">
                    @include('partials.whatsapp-icon') WhatsApp
                  </a>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      @endif
    @endforeach
  </div>

  @if($conMapa->isNotEmpty())
    <div class="cms-sucursales-maps">
      <div class="cms-catrot-badge">Ubicaciones</div>
      <h2 class="cms-titulo cms-titulo-mediano">Nuestras ubicaciones</h2>
      <div class="cms-sucursales-maps-grid">
        @foreach($conMapa as $s)
          <div class="cms-sucursal-map-card">
            <h4>{{ $s['nombre'] ?? '' }}</h4>
            <p>{{ $s['ciudad'] ?? '' }}</p>
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($s['direccion']) }}" target="_blank" rel="noopener">Abrir en Maps ↗</a>
            <iframe src="https://www.google.com/maps?q={{ urlencode($s['direccion']) }}&output=embed" loading="lazy"></iframe>
          </div>
        @endforeach
      </div>
    </div>
  @endif
</div>
