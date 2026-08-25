@extends('admin.layout')

@section('title', 'Promociones')

@section('topbar-actions')
  <a href="{{ route('admin.promociones.create') }}" class="btn btn-primary">+ Nueva promoción</a>
@endsection

@section('content')

<p class="form-hint" style="margin-bottom:20px;">Las recurrentes (Navidad, Día de la Madre, etc.) ya vienen con la fecha calculada cada año — solo hace falta activarlas cuando quieras usarlas. "En expectativa" es el aviso sutil antes de que arranque la promo; "Activa" es cuando ya se muestra la barra dorada con descuento.</p>

<div class="admin-table-wrap">
  @if($promotions->isEmpty())
    <div class="admin-empty">Todavía no hay promociones.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Tipo</th>
          <th>Fechas</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($promotions as $promotion)
          <tr>
            <td>
              <strong>{{ $promotion->name }}</strong>
              @if($promotion->show_as_modal)
                <span style="color:var(--text-muted);font-size:12px;"> · con modal</span>
              @endif
            </td>
            <td style="color:var(--text-secondary);">
              @if($promotion->is_recurring)
                Recurrente · {{ str_pad($promotion->recurring_day, 2, '0', STR_PAD_LEFT) }}/{{ str_pad($promotion->recurring_month, 2, '0', STR_PAD_LEFT) }}
              @else
                Puntual
              @endif
            </td>
            <td class="mono" style="color:var(--text-secondary);font-size:12.5px;">
              {{ $promotion->starts_at->format('d/m/Y') }} – {{ $promotion->ends_at->format('d/m/Y') }}
            </td>
            <td>
              @if(! $promotion->active)
                <span class="status-badge inactive">Inactiva</span>
              @elseif($promotion->isActiveNow())
                <span class="status-badge active">Activa</span>
              @elseif($promotion->isInTeaserNow())
                <span class="status-badge teaser">En expectativa</span>
              @else
                <span class="status-badge inactive">Programada</span>
              @endif
            </td>
            <td>
              <div class="cell-actions">
                <form method="POST" action="{{ route('admin.promociones.toggle-active', $promotion) }}">
                  @csrf @method('PATCH')
                  <button type="submit" class="btn btn-sm">{{ $promotion->active ? 'Desactivar' : 'Activar' }}</button>
                </form>
                <a href="{{ route('admin.promociones.edit', $promotion) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.promociones.destroy', $promotion) }}" onsubmit="return confirm('¿Eliminar {{ $promotion->name }}? Esta acción no se puede deshacer.');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

@endsection
