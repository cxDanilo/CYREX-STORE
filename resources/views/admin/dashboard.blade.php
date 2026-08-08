@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')

<div class="admin-stat-grid">
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $stats['productos_activos'] }}</div>
    <div class="admin-stat-label">Productos activos</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $stats['productos_inactivos'] }}</div>
    <div class="admin-stat-label">Productos en privado</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $stats['categorias'] }}</div>
    <div class="admin-stat-label">Categorías</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $stats['paginas_publicadas'] }}</div>
    <div class="admin-stat-label">Páginas publicadas</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-value">{{ $stats['usuarios'] }}</div>
    <div class="admin-stat-label">Usuarios</div>
  </div>
</div>

<div class="admin-dashboard-grid">
  <div class="cms-editor-panel">
    <h4>Sin stock</h4>
    @if($sinStock->isEmpty())
      <p class="form-hint">Ningún producto activo está agotado ahora mismo.</p>
    @else
      @foreach($sinStock as $product)
        <div class="admin-dashboard-row">
          <a href="{{ route('admin.productos.edit', $product) }}">{{ $product->name }}</a>
          <span class="mono" style="color:var(--red);">0</span>
        </div>
      @endforeach
    @endif
  </div>

  <div class="cms-editor-panel">
    <h4>Stock bajo (5 o menos)</h4>
    @if($stockBajo->isEmpty())
      <p class="form-hint">Nada por debajo de 5 unidades ahora mismo.</p>
    @else
      @foreach($stockBajo as $product)
        <div class="admin-dashboard-row">
          <a href="{{ route('admin.productos.edit', $product) }}">{{ $product->name }}</a>
          <span class="mono" style="color:var(--gold);">{{ $product->stock }}</span>
        </div>
      @endforeach
    @endif
  </div>

  <div class="cms-editor-panel" style="grid-column:1/-1;">
    <h4>Actividad reciente</h4>
    @if($actividadReciente->isEmpty())
      <p class="form-hint">Todavía no hay cambios registrados.</p>
    @else
      @foreach($actividadReciente as $log)
        <div class="admin-dashboard-row">
          <span>
            <strong>{{ $log->user_name }}</strong>
            {{ ['created' => 'creó', 'updated' => 'editó', 'deleted' => 'eliminó'][$log->action] ?? $log->action }}
            <a href="{{ route('admin.historial.index', ['product_id' => $log->product_id]) }}">{{ $log->product_name }}</a>
          </span>
          <span class="mono" style="color:var(--text-muted);font-size:12px;">{{ $log->created_at->diffForHumans() }}</span>
        </div>
      @endforeach
      <div style="margin-top:14px;">
        <a href="{{ route('admin.historial.index') }}" class="btn btn-sm">Ver historial completo</a>
      </div>
    @endif
  </div>
</div>

@endsection
