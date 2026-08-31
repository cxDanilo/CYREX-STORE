@extends('admin.layout')

@section('title', 'Combos')

@section('topbar-actions')
  <a href="{{ route('admin.combos.create') }}" class="btn btn-primary">+ Nuevo combo</a>
@endsection

@section('content')

<p class="form-hint" style="margin-bottom:20px;">Un combo empaqueta varios productos ya elegidos (ej. una build de "Arma tu PC") a un precio fijo — se muestra en la home con el bloque "Combos y ofertas" y se agrega al carrito como una sola línea a ese precio.</p>

<div class="admin-table-wrap">
  @if($combos->isEmpty())
    <div class="admin-empty">Todavía no hay combos.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Productos</th>
          <th>Precio</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($combos as $combo)
          <tr>
            <td class="admin-table-title"><strong>{{ $combo->name }}</strong></td>
            <td style="color:var(--text-secondary);" data-label="Productos">{{ $combo->products_count }}</td>
            <td class="mono" style="color:var(--text-secondary);" data-label="Precio">
              @if($combo->currency === 'USD')
                ${{ number_format($combo->price, 2) }}
              @else
                Bs {{ number_format($combo->price, 2) }}
              @endif
            </td>
            <td data-label="Estado">
              <span class="status-badge {{ $combo->active ? 'active' : 'inactive' }}">{{ $combo->active ? 'Activo' : 'Inactivo' }}</span>
            </td>
            <td class="cell-actions">
              <div class="cell-actions">
                <form method="POST" action="{{ route('admin.combos.toggle-active', $combo) }}">
                  @csrf @method('PATCH')
                  <button type="submit" class="btn btn-sm">{{ $combo->active ? 'Desactivar' : 'Activar' }}</button>
                </form>
                <a href="{{ route('combo.show', $combo->slug) }}" target="_blank" class="btn btn-sm">Ver</a>
                <a href="{{ route('admin.combos.edit', $combo) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.combos.destroy', $combo) }}" onsubmit="return confirm('¿Eliminar {{ $combo->name }}? Esta acción no se puede deshacer.');">
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
