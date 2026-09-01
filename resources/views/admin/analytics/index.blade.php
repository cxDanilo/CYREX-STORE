@extends('admin.layout')

@section('title', 'Analítica')

@section('content')

@php
  $maxTrend = max(1, collect($trend)->max('total'));

  $duracion = $engagement['avgSeconds'] > 0
    ? floor($engagement['avgSeconds'] / 60) . 'm ' . str_pad($engagement['avgSeconds'] % 60, 2, '0', STR_PAD_LEFT) . 's'
    : '—';
@endphp

<div class="admin-stat-grid">
  <div class="admin-stat-card analytics-online-card">
    <div class="analytics-online-head">
      <span class="analytics-online-dot"></span>
      <div class="admin-stat-value" id="analytics-online-count" style="margin-bottom:0;">{{ $onlineCount }}</div>
    </div>
    <div class="admin-stat-label">Conectados ahora</div>
    <div class="analytics-online-list" id="analytics-online-list"></div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $summary['hoy']['visitas'] }}</div>
    <div class="admin-stat-label">Visitas hoy · {{ $summary['hoy']['vistas'] }} vistas</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $summary['ayer']['visitas'] }}</div>
    <div class="admin-stat-label">Visitas ayer · {{ $summary['ayer']['vistas'] }} vistas</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $summary['7d']['visitas'] }}</div>
    <div class="admin-stat-label">Últimos 7 días · {{ $summary['7d']['vistas'] }} vistas</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $summary['28d']['visitas'] }}</div>
    <div class="admin-stat-label">Últimos 28 días · {{ $summary['28d']['vistas'] }} vistas</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $duracion }}</div>
    <div class="admin-stat-label">Tiempo promedio en el sitio (sesiones con más de 1 página)</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $engagement['bounceRate'] }}%</div>
    <div class="admin-stat-label">Rebote (vieron una sola página)</div>
  </div>
</div>

<div class="form-section">
  <h3>Tendencia — últimos 14 días</h3>
  <div class="form-hint" style="margin-bottom:14px;">Visitas nuevas por día (sesiones distintas, no vistas de página).</div>
  <div class="analytics-trend">
    @foreach($trend as $day)
      <div class="analytics-trend-bar">
        <span class="analytics-trend-bar-n">{{ $day['total'] }}</span>
        <div class="analytics-trend-bar-fill" style="height:{{ max(3, round(($day['total'] / $maxTrend) * 100)) }}%;"></div>
        <span class="analytics-trend-bar-label">{{ $day['label'] }}</span>
      </div>
    @endforeach
  </div>
</div>

<div class="admin-dashboard-grid">
  <div class="cms-editor-panel">
    <h4>Páginas principales</h4>
    <div class="form-hint" style="margin-bottom:6px;">Últimos 30 días, por vistas.</div>
    @forelse($topPages as $row)
      <div class="analytics-rank-row">
        <span class="label">{{ $row->page_label }}</span>
        <span class="n">{{ $row->total }}</span>
      </div>
    @empty
      <p class="analytics-empty">Todavía no hay suficientes datos.</p>
    @endforelse
  </div>

  <div class="cms-editor-panel">
    <h4>Por dónde se van</h4>
    <div class="form-hint" style="margin-bottom:6px;">Última página vista antes de cortar la sesión — últimos 30 días.</div>
    @forelse($exitPages as $row)
      <div class="analytics-rank-row">
        <span class="label">{{ $row->exit_label }}</span>
        <span class="n">{{ $row->total }}</span>
      </div>
    @empty
      <p class="analytics-empty">Todavía no hay suficientes datos.</p>
    @endforelse
  </div>
</div>

<div class="admin-dashboard-grid" style="margin-top:16px;">
  <div class="cms-editor-panel">
    <h4>Dispositivo</h4>
    <div class="form-hint" style="margin-bottom:6px;">Últimos 30 días.</div>
    @forelse($devices as $row)
      <div class="analytics-rank-row">
        <span class="label">{{ $row['label'] }}</span>
        <span class="n">{{ $row['total'] }}</span>
      </div>
    @empty
      <p class="analytics-empty">Todavía no hay suficientes datos.</p>
    @endforelse
  </div>

  <div class="cms-editor-panel">
    <h4>De dónde vienen</h4>
    <div class="form-hint" style="margin-bottom:6px;">Dominio de referencia — últimos 30 días. "directo" incluye enlaces de WhatsApp y apps que no mandan referente.</div>
    @forelse($referrers as $row)
      <div class="analytics-rank-row">
        <span class="label">{{ $row->referrer_domain }}</span>
        <span class="n">{{ $row->total }}</span>
      </div>
    @empty
      <p class="analytics-empty">Todavía no hay suficientes datos.</p>
    @endforelse
  </div>

  <div class="cms-editor-panel">
    <h4>Navegador</h4>
    <div class="form-hint" style="margin-bottom:6px;">Últimos 30 días.</div>
    @forelse($browsers as $row)
      <div class="analytics-rank-row">
        <span class="label">{{ $row['label'] }}</span>
        <span class="n">{{ $row['total'] }}</span>
      </div>
    @empty
      <p class="analytics-empty">Todavía no hay suficientes datos.</p>
    @endforelse
  </div>

  <div class="cms-editor-panel">
    <h4>Sistema operativo</h4>
    <div class="form-hint" style="margin-bottom:6px;">Últimos 30 días.</div>
    @forelse($systems as $row)
      <div class="analytics-rank-row">
        <span class="label">{{ $row['label'] }}</span>
        <span class="n">{{ $row['total'] }}</span>
      </div>
    @empty
      <p class="analytics-empty">Todavía no hay suficientes datos.</p>
    @endforelse
  </div>
</div>

<div class="form-section">
  <h3>Productos más buscados</h3>
  <div class="form-hint" style="margin-bottom:6px;">Búsquedas hechas desde la tienda (no cuenta el autocompletado) — últimos 30 días.</div>
  @forelse($topSearches as $row)
    <div class="analytics-rank-row">
      <span class="label">"{{ $row->query }}"</span>
      <span class="n">{{ $row->total }}</span>
    </div>
  @empty
    <p class="analytics-empty">Todavía no se registró ninguna búsqueda.</p>
  @endforelse
</div>

<script>
(function () {
  var countEl = document.getElementById('analytics-online-count');
  var listEl = document.getElementById('analytics-online-list');

  function refresh() {
    fetch('{{ route('admin.analitica.online') }}', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        countEl.textContent = data.count;
        listEl.innerHTML = data.visitors.map(function (v) {
          return '<div class="analytics-online-row"><span class="p">' + v.page + '</span><span class="t">' + v.hace + '</span></div>';
        }).join('');
      })
      .catch(function () {});
  }

  refresh();
  setInterval(refresh, 15000);
})();
</script>

@endsection
