@extends('admin.layout')

@section('title', 'Menús')

@section('topbar-actions')
  <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">+ Nuevo menú</a>
@endsection

@section('content')

<p class="form-hint" style="margin-bottom:20px;">El menú con key <code class="mono">header_nav</code> controla los links del header (hoy "Inicio" y "Tienda").</p>

<div class="admin-table-wrap">
  @if($menus->isEmpty())
    <div class="admin-empty">Todavía no hay menús.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Key</th>
          <th>Ítems</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($menus as $menu)
          <tr>
            <td>{{ $menu->name }}</td>
            <td class="mono" style="color:var(--text-secondary);">{{ $menu->key }}</td>
            <td class="mono">{{ $menu->items_count }}</td>
            <td>
              <div class="cell-actions">
                <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" onsubmit="return confirm('¿Eliminar el menú {{ $menu->name }}?');">
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
