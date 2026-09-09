@extends('admin.layout')

@section('title', 'Productos')
@section('page-description', 'Gestiona el catálogo: precios, stock, ofertas y visibilidad de cada producto.')

@section('topbar-actions')
  <a href="{{ route('admin.productos.create') }}" class="btn btn-primary" data-tour="producto-nuevo-btn">+ Nuevo producto</a>
@endsection

@section('content')

<form method="GET" class="admin-search">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre...">
  <button type="submit" class="btn btn-sm">Buscar</button>
</form>

<div class="admin-table-wrap" x-data="{ editing: null }">
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
              @if($product->offer_selected)
                <span style="color:var(--gold);font-size:12px;"> · en oferta</span>
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
              @if($product->is_sold_out)
                <span class="status-badge inactive">Agotado</span>
              @endif
            </td>
            <td class="cell-actions">
              <div class="cell-actions">
                <form method="POST" action="{{ route('admin.productos.toggle-status', $product) }}">
                  @csrf @method('PATCH')
                  <button type="submit" class="btn btn-sm">{{ $product->status === 'active' ? 'Poner en privado' : 'Publicar' }}</button>
                </form>
                <button type="button" class="btn btn-sm" x-show="editing !== {{ $product->id }}" @click="editing = {{ $product->id }}">Edición rápida</button>
                <button type="button" class="btn btn-sm" x-show="editing === {{ $product->id }}" x-cloak @click="editing = null">Cerrar</button>
                <a href="{{ route('admin.productos.edit', $product) }}" class="btn btn-sm" data-tour="producto-editar-link">Editar</a>
                <form method="POST" action="{{ route('admin.productos.destroy', $product) }}" onsubmit="return confirm('¿Eliminar {{ $product->name }}? Esta acción no se puede deshacer.');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
          <tr x-show="editing === {{ $product->id }}" x-cloak>
            <td colspan="6" style="background:var(--bg-elevated-2);">
              <form method="POST" action="{{ route('admin.productos.quick-edit', $product) }}"
                    x-data="{ onOffer: {{ $product->offer_selected ? 'true' : 'false' }} }"
                    style="display:flex;flex-direction:column;gap:12px;max-width:480px;padding:6px 0;">
                @csrf
                @method('PATCH')

                <label class="switch">
                  <input type="checkbox" name="is_sold_out" value="1" {{ $product->is_sold_out ? 'checked' : '' }}>
                  <span class="switch-track"></span>
                  <span class="switch-label">Agotado</span>
                </label>

                <label class="switch">
                  <input type="checkbox" name="offer_selected" value="1" x-model="onOffer">
                  <span class="switch-track"></span>
                  <span class="switch-label">En oferta</span>
                </label>

                <div x-show="onOffer" x-cloak style="display:flex;flex-direction:column;gap:10px;padding-left:24px;">
                  <div class="form-group" style="margin:0;">
                    <label>Precio de oferta</label>
                    <input type="number" step="0.01" min="0.01" name="offer_price" value="{{ old('offer_price', $product->offer_price) }}">
                    @error('offer_price') <div class="error">{{ $message }}</div> @enderror
                  </div>

                  @if($activeDiscountGroup)
                    <div class="form-hint" style="margin:0;">Termina junto con la campaña «{{ $activeDiscountGroup->name }}» el {{ $activeDiscountGroup->ends_at->timezone('America/La_Paz')->format('d/m/Y H:i') }}.</div>
                  @else
                    <div class="form-group" style="margin:0;">
                      <label>Termina (hora de Bolivia)</label>
                      <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}">
                      @error('ends_at') <div class="error">{{ $message }}</div> @enderror
                    </div>
                  @endif
                </div>

                <button type="submit" class="btn btn-sm btn-primary" style="align-self:flex-start;">Guardar</button>
              </form>
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
