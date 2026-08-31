@extends('admin.layout')

@section('title', 'Productos')

@section('topbar-actions')
  <a href="{{ route('admin.productos.create') }}" class="btn btn-primary">+ Nuevo producto</a>
@endsection

@section('content')

<form method="GET" class="admin-search">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre...">
  <button type="submit" class="btn btn-sm">Buscar</button>
</form>

<div class="admin-table-wrap">
  @if($products->isEmpty())
    <div class="admin-empty">No hay productos que coincidan.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th></th>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Precio</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($products as $product)
          <tr>
            <td>
              <div style="width:40px;height:40px;border-radius:8px;background:var(--bg-elevated-2);overflow:hidden;">
                @if($product->image_url)
                  <img src="{{ $product->image_url }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                @endif
              </div>
            </td>
            <td class="admin-table-title">
              {{ $product->name }}
              @if($product->has_variants)
                <span style="color:var(--text-muted);font-size:12px;"> · variantes</span>
              @endif
            </td>
            <td style="color:var(--text-secondary);" data-label="Categoría">{{ $product->category->name }}</td>
            <td class="mono" data-label="Precio">
              @if($product->currency === 'USD')
                ${{ number_format($product->price, 2) }}
              @else
                Bs {{ number_format($product->price, 2) }}
              @endif
            </td>
            <td data-label="Estado">
              <span class="status-badge {{ $product->status }}">{{ $product->status === 'active' ? 'Publicado' : 'Privado' }}</span>
            </td>
            <td class="cell-actions">
              <div class="cell-actions">
                <form method="POST" action="{{ route('admin.productos.toggle-status', $product) }}">
                  @csrf @method('PATCH')
                  <button type="submit" class="btn btn-sm">{{ $product->status === 'active' ? 'Poner en privado' : 'Publicar' }}</button>
                </form>
                <a href="{{ route('admin.productos.edit', $product) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.productos.destroy', $product) }}" onsubmit="return confirm('¿Eliminar {{ $product->name }}? Esta acción no se puede deshacer.');">
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

<div style="margin-top:20px;">
  {{ $products->links('partials.pagination') }}
</div>

@endsection
