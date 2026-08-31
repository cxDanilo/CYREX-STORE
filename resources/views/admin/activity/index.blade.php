@extends('admin.layout')

@section('title', 'Historial de cambios')

@section('content')

@if(request('product_id'))
  <div style="margin-bottom:16px;">
    <a href="{{ route('admin.historial.index') }}" class="btn btn-sm">Quitar filtro ×</a>
  </div>
@endif

<div class="admin-table-wrap">
  @if($logs->isEmpty())
    <div class="admin-empty">Todavía no hay cambios registrados.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Fecha y hora</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Producto</th>
          <th>Qué cambió</th>
        </tr>
      </thead>
      <tbody>
        @foreach($logs as $log)
          <tr>
            <td class="mono" style="color:var(--text-muted);font-size:12.5px;white-space:nowrap;" data-label="Fecha">{{ $log->created_at->format('d/m/Y H:i') }}</td>
            <td data-label="Usuario">{{ $log->user_name }}</td>
            <td data-label="Acción">
              @php
                $actionLabel = ['created' => 'Creó', 'updated' => 'Editó', 'deleted' => 'Eliminó'][$log->action] ?? $log->action;
                $actionColor = ['created' => 'var(--green)', 'updated' => 'var(--gold)', 'deleted' => 'var(--red)'][$log->action] ?? 'var(--text-secondary)';
              @endphp
              <span class="mono" style="color:{{ $actionColor }};">{{ $actionLabel }}</span>
            </td>
            <td class="admin-table-title">
              @if($log->product)
                <a href="{{ route('admin.productos.edit', $log->product) }}">{{ $log->product_name }}</a>
              @else
                <span style="color:var(--text-muted);">{{ $log->product_name }} (eliminado)</span>
              @endif
            </td>
            <td style="max-width:420px;" data-label="Qué cambió" class="admin-table-block">
              @if(empty($log->changes))
                <span style="color:var(--text-muted);">—</span>
              @else
                <div style="display:flex;flex-direction:column;gap:4px;">
                  @foreach($log->changes as $field => $change)
                    <div style="font-size:12.5px;">
                      <span class="mono" style="color:var(--text-muted);">{{ $field }}:</span>
                      <span style="color:var(--red);text-decoration:line-through;">{{ \Illuminate\Support\Str::limit((string) ($change['antes'] ?? '—'), 40) }}</span>
                      →
                      <span style="color:var(--green);">{{ \Illuminate\Support\Str::limit((string) ($change['despues'] ?? '—'), 40) }}</span>
                    </div>
                  @endforeach
                </div>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div style="margin-top:20px;">
  {{ $logs->links('partials.pagination') }}
</div>

@endsection
