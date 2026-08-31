@extends('admin.layout')

@section('title', $combo->exists ? 'Editar combo' : 'Nuevo combo')

@section('content')

@php
  $selectedIds = old('product_ids', $combo->products->pluck('id')->all());
@endphp

<div x-data="{
      q: '',
      selected: @js(collect($selectedIds)->map(fn ($id) => (string) $id)->all()),
      get total() {
        return this.selected.reduce((sum, id) => sum + (this.prices[id] || 0), 0);
      },
      prices: @js($categorizedProducts->flatten()->mapWithKeys(fn ($p) => [(string) $p->id => round($p->priceInUsd($rate), 2)])),
    }" style="max-width:760px;">
  <form method="POST" action="{{ $combo->exists ? route('admin.combos.update', $combo) : route('admin.combos.store') }}" class="admin-form">
    @csrf
    @if($combo->exists) @method('PUT') @endif

    <div class="form-section">
      <h3>Información general</h3>

      <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $combo->name) }}" required
               x-on:input="if(!$refs.slug.dataset.touched) $refs.slug.value = window.autoSlugify($event.target.value)">
        <div class="form-hint">Ej. "Combo Gamer Inicial" — es lo que ve el cliente en la home y en la página del combo.</div>
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="slug">Slug</label>
        <input type="text" id="slug" name="slug" x-ref="slug" value="{{ old('slug', $combo->slug) }}" required
               x-on:input="$event.target.dataset.touched = true">
        @error('slug') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="description">Descripción (opcional)</label>
        <textarea id="description" name="description" rows="3">{{ old('description', $combo->description) }}</textarea>
        @error('description') <div class="error">{{ $message }}</div> @enderror
      </div>

      <label style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="active" value="1" {{ old('active', $combo->active) ? 'checked' : '' }}>
        Activo
      </label>
      <div class="form-hint">Apagalo para dejarlo armado sin que se muestre todavía en la home.</div>
    </div>

    <div class="form-section">
      <h3>Precio del combo</h3>
      <div class="form-row">
        <div class="form-group">
          <label for="price">Precio</label>
          <input type="number" id="price" name="price" step="0.01" min="0" value="{{ old('price', $combo->price) }}" required>
          @error('price') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
          <label for="currency">Moneda</label>
          <select id="currency" name="currency" required>
            <option value="USD" {{ old('currency', $combo->currency) === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="BOB" {{ old('currency', $combo->currency) === 'BOB' ? 'selected' : '' }}>BOB</option>
          </select>
          @error('currency') <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>
      <div class="form-hint">
        Precio sumado de los productos elegidos abajo (individual, en USD):
        <strong class="mono" x-text="'$' + total.toFixed(2)"></strong>
        — este precio del combo es el que se cobra como línea única en el carrito, no se recalcula de los productos.
      </div>
    </div>

    <div class="form-section">
      <h3>Productos incluidos</h3>
      <div class="form-hint" style="margin-bottom:10px;">El orden en que los marques acá es el orden en que se muestran en la página del combo. La foto del combo es la del primer producto elegido.</div>
      @error('product_ids') <div class="error" style="margin-bottom:10px;">{{ $message }}</div> @enderror

      <input type="text" x-model="q" placeholder="Buscar producto..." class="admin-product-search">

      <div style="max-height:420px;overflow-y:auto;border:1px solid var(--border);border-radius:10px;padding:14px;">
        @foreach($categorizedProducts as $categoryName => $products)
          <div>
            <div style="font-family:var(--font-mono);font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin:14px 0 6px;">{{ $categoryName }}</div>
            @foreach($products as $product)
              <label class="combo-product-row"
                     x-show="!q || '{{ \Illuminate\Support\Str::lower($product->name) }}'.includes(q.toLowerCase())">
                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                       x-model="selected"
                       {{ in_array($product->id, $selectedIds) ? 'checked' : '' }}>
                <span class="combo-product-row-info">
                  <span class="combo-product-row-name">{{ $product->name }}</span>
                  <span class="combo-product-row-price mono">
                    @if($product->currency === 'USD')
                      ${{ number_format($product->price, 2) }}
                    @else
                      Bs {{ number_format($product->price, 2) }}
                    @endif
                  </span>
                </span>
              </label>
            @endforeach
          </div>
        @endforeach
      </div>
      <div class="form-hint" style="margin-top:10px;"><span x-text="selected.length"></span> producto(s) elegido(s) — mínimo 2.</div>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.combos.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
  </form>
</div>

@endsection
