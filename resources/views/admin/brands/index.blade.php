@extends('admin.layout')

@section('title', 'Marcas')
@section('page-description', 'Marcas que se pueden elegir al cargar un producto (ej. Ajazz, Thermalright).')

@section('content')

<div class="admin-table-wrap">
  <form method="POST" action="{{ route('admin.marcas.store') }}" style="display:flex;gap:10px;align-items:flex-start;margin-bottom:24px;max-width:460px;">
    @csrf
    <div class="form-group" style="flex:1;margin-bottom:0;">
      <label for="name">Nueva marca</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Ej. Ajazz" required autofocus autocomplete="off">
      @error('name')
        <p class="form-hint" style="color:var(--red);">{{ $message }}</p>
      @enderror
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top:24px;">Agregar</button>
  </form>

  @if($brands->isEmpty())
    <div class="admin-empty">Todavía no cargaste ninguna marca.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Marca</th>
          <th>Productos con esta marca</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($brands as $brand)
          <tr>
            <td class="admin-table-title">{{ $brand->name }}</td>
            <td class="mono" data-label="Productos" style="color:var(--text-secondary);">{{ $counts[$brand->name] ?? 0 }}</td>
            <td class="cell-actions">
              <form method="POST" action="{{ route('admin.marcas.destroy', $brand) }}" onsubmit="return confirm('¿Eliminar la marca &quot;{{ $brand->name }}&quot;? Los productos que ya la tengan cargada no cambian, solo deja de aparecer para elegir en productos nuevos.');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

@endsection
