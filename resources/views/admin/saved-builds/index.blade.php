@extends('admin.layout')

@section('title', 'Armados publicados')
@section('page-description', 'Armados de "Arma tu PC" que visitantes publicaron para la galería pública — revisá antes de aprobar.')

@section('content')

<p class="form-hint" style="margin-bottom:20px;">Cualquier visitante puede armar una PC en "Arma tu PC" y publicarla con un nombre puesto por él mismo — nada aparece en <a href="{{ route('saved-builds.index') }}" target="_blank">/armados</a> hasta que lo apruebes acá.</p>

<div class="admin-table-wrap">
  @if($builds->isEmpty())
    <div class="admin-empty">Todavía no publicaron ningún armado.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Armado</th>
          <th>Piezas</th>
          <th>Total</th>
          <th>Estado</th>
          <th>Publicado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($builds as $build)
          <tr>
            <td class="admin-table-title">
              <strong>{{ $build->visitor_name }}</strong>
              @if($build->platform)
                <div style="color:var(--text-muted);font-size:12px;">{{ $build->platform }}</div>
              @endif
            </td>
            <td style="color:var(--text-secondary);" data-label="Piezas">{{ $build->items_count }}</td>
            <td class="mono" style="color:var(--text-secondary);" data-label="Total">${{ number_format($build->total_usd, 2) }}</td>
            <td data-label="Estado">
              @if($build->status === 'approved')
                <span class="status-badge active">Publicado</span>
              @elseif($build->status === 'rejected')
                <span class="status-badge inactive">Rechazado</span>
              @else
                <span class="status-badge teaser">Pendiente</span>
              @endif
            </td>
            <td style="color:var(--text-secondary);" data-label="Publicado">{{ $build->created_at->diffForHumans() }}</td>
            <td class="cell-actions">
              <div class="cell-actions">
                <a href="{{ route('admin.saved-builds.show', $build) }}" class="btn btn-sm">Ver</a>
                @if($build->status !== 'approved')
                  <form method="POST" action="{{ route('admin.saved-builds.approve', $build) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-primary">Aprobar</button>
                  </form>
                @endif
                @if($build->status !== 'rejected')
                  <form method="POST" action="{{ route('admin.saved-builds.reject', $build) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm">Rechazar</button>
                  </form>
                @endif
                <form method="POST" action="{{ route('admin.saved-builds.destroy', $build) }}" onsubmit="return confirm('¿Eliminar el armado de {{ $build->visitor_name }}? Esta acción no se puede deshacer.');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <div style="margin-top:16px;">{{ $builds->links('partials.pagination') }}</div>
  @endif
</div>

@endsection
