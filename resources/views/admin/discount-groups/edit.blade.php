@extends('admin.layout')

@section('title', 'Descuentos')
@section('page-description', 'Campañas de oferta con fecha de fin — se autolimpian solas al vencer.')

@section('content')

@php
  $allProducts = $categorizedProducts->flatten();
  $selectedIds = old('product_ids', $group ? $group->products->pluck('id')->all() : []);
  $priceMap = [];
  foreach ($allProducts as $p) {
      $priceMap[$p->id] = old('offer_price.'.$p->id, $p->offer_price);
  }
  $endsAtLocal = $group ? $group->ends_at->timezone('America/La_Paz')->format('Y-m-d\TH:i') : '';
@endphp

<p class="form-hint" style="margin-bottom:20px;">Los productos que sumes acá muestran su precio real tachado + el precio de oferta, con cuenta regresiva, en toda la tienda — sin tocar el precio real de cada producto. Al llegar la fecha de fin, la campaña se borra sola y todos vuelven a su precio normal (no hace falta apagar nada a mano).</p>

@if($group)
  <div class="form-section" style="margin-bottom:20px;">
    <form method="POST" action="{{ route('admin.descuentos.destroy', $group) }}" onsubmit="return confirm('¿Terminar la campaña «{{ $group->name }}» ahora? Los productos vuelven a su precio normal de inmediato.');">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-danger btn-sm">Terminar campaña ahora</button>
    </form>
  </div>
@endif

<div x-data="{
      q: '',
      selected: @js(collect($selectedIds)->map(fn ($id) => (string) $id)->all()),
      prices: @js(collect($priceMap)->mapWithKeys(fn ($v, $k) => [(string) $k => $v])->all()),
    }" style="max-width:760px;">
  <form method="POST" action="{{ $group ? route('admin.descuentos.update', $group) : route('admin.descuentos.store') }}" class="admin-form">
    @csrf
    @if($group)
      @method('PUT')
    @endif

    <div class="form-section">
      <h3>{{ $group ? 'Editar campaña' : 'Nueva campaña' }}</h3>

      <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $group->name ?? '') }}" placeholder="Ej. Días Amarillos" required>
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group" style="margin-top:16px;">
        <label for="ends_at">Termina (hora de Bolivia)</label>
        <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $endsAtLocal) }}" required>
        <div class="form-hint">Misma fecha para todos los productos de la campaña — la cuenta regresiva de cada tarjeta cuenta hasta acá.</div>
        @error('ends_at') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Productos en la campaña</h3>
      <div class="form-hint" style="margin-bottom:10px;">Desmarcar un producto lo saca de esta campaña (no borra el precio que le pusiste, por si lo volvés a sumar).</div>

      <input type="text" x-model="q" placeholder="Buscar producto..." class="admin-product-search">

      <div style="max-height:480px;overflow-y:auto;border:1px solid var(--border);border-radius:10px;padding:14px;">
        @foreach($categorizedProducts as $categoryName => $products)
          <div>
            <div style="font-family:var(--font-mono);font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin:14px 0 6px;">{{ $categoryName }}</div>
            @foreach($products as $product)
              @php
                $hasVariantOverride = $product->has_variants && $product->variants->contains(fn ($v) => $v->price_override !== null);
              @endphp
              <label class="combo-product-row offer-product-row"
                     x-show="!q || '{{ \Illuminate\Support\Str::lower($product->name) }}'.includes(q.toLowerCase())">
                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                       x-model="selected"
                       {{ in_array($product->id, $selectedIds) ? 'checked' : '' }}>
                <span class="combo-product-row-info">
                  <span class="combo-product-row-name">
                    {{ $product->name }}
                    @if($hasVariantOverride)
                      <span title="Tiene variantes con precio propio — esa variante sigue cobrando su propio precio, no el de oferta.">⚠️</span>
                    @endif
                  </span>
                  <span class="combo-product-row-price mono">
                    @if($product->currency === 'USD')
                      ${{ number_format($product->price, 2) }}
                    @else
                      Bs {{ number_format($product->price, 2) }}
                    @endif
                  </span>
                </span>
                <input type="number" step="0.01" min="0.01" class="offer-price-input mono"
                       name="offer_price[{{ $product->id }}]"
                       x-show="selected.includes('{{ $product->id }}')"
                       x-model="prices['{{ $product->id }}']"
                       placeholder="Precio oferta"
                       @click.stop>
                @error('offer_price.'.$product->id)
                  <div class="error" style="width:100%;order:99;">{{ $message }}</div>
                @enderror
              </label>
            @endforeach
          </div>
        @endforeach
      </div>
      <div class="form-hint" style="margin-top:10px;"><span x-text="selected.length"></span> producto(s) en la campaña.</div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
  </form>
</div>

@endsection
