@extends('admin.layout')

@section('title', 'Categorías')

@section('topbar-actions')
  <a href="{{ route('admin.categorias.create') }}" class="btn btn-primary">+ Nueva categoría</a>
@endsection

@section('content')

<p class="form-hint" style="margin-bottom:20px;">Las categorías padre son las que aparecen como principales en la tienda y en el menú flotante. Las hijas solo se muestran al pasar el mouse sobre su categoría padre.</p>

<div class="admin-table-wrap">
  @if($categories->isEmpty())
    <div class="admin-empty">Todavía no hay categorías.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th></th>
          <th>Nombre</th>
          <th>Slug</th>
          <th>Productos</th>
          <th>Orden</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($categories as $parent)
          <tr>
            <td style="width:32px;color:var(--gold);">@include('partials.category-icon', ['icon' => $parent->icon])</td>
            <td><strong>{{ $parent->name }}</strong></td>
            <td class="mono" style="color:var(--text-secondary);">{{ $parent->slug }}</td>
            <td class="mono">{{ $parent->products_count }}</td>
            <td class="mono">{{ $parent->sort_order }}</td>
            <td>
              <div class="cell-actions">
                <a href="{{ route('admin.categorias.edit', $parent) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.categorias.destroy', $parent) }}" onsubmit="return confirm('¿Eliminar {{ $parent->name }}? Esta acción no se puede deshacer.');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
          @foreach($parent->children as $child)
            <tr>
              <td></td>
              <td style="padding-left:34px;color:var(--text-secondary);">— {{ $child->name }}</td>
              <td class="mono" style="color:var(--text-secondary);">{{ $child->slug }}</td>
              <td class="mono">{{ $child->products_count }}</td>
              <td class="mono">{{ $child->sort_order }}</td>
              <td>
                <div class="cell-actions">
                  <a href="{{ route('admin.categorias.edit', $child) }}" class="btn btn-sm">Editar</a>
                  <form method="POST" action="{{ route('admin.categorias.destroy', $child) }}" onsubmit="return confirm('¿Eliminar {{ $child->name }}? Esta acción no se puede deshacer.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  @endif
</div>

@endsection
