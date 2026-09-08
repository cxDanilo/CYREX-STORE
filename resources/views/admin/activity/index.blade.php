@extends('admin.layout')

@section('title', 'Historial de cambios')
@section('page-description', 'Quién cambió qué y cuándo, producto por producto.')

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
                <div style="display:flex;flex-direction:column;gap:6px;">
                  @foreach($log->changes as $field => $change)
                    @foreach(\App\Models\ProductActivityLog::describeChange($change['antes'] ?? null, $change['despues'] ?? null, $field) as $row)
                      <div style="font-size:12.5px;">
                        <span style="color:var(--text-secondary);font-weight:600;">{{ \App\Models\ProductActivityLog::fieldLabel($field) }}{{ $row['label'] ? " ({$row['label']})" : '' }}:</span>
                        @if($row['before'] === null)
                          <span style="color:var(--text-muted);">sin cambios visibles</span>
                        @else
                          <span style="color:var(--red);text-decoration:line-through;">{{ $row['before'] }}</span>
                          →
                          <span style="color:var(--green);">{{ $row['after'] }}</span>
                        @endif
                      </div>
                    @endforeach
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
