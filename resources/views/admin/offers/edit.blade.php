@extends('admin.layout')

@section('title', 'Ofertas')

@section('content')

@php
  $allProducts = $categorizedProducts->flatten();
  $selectedIds = old('product_ids', $allProducts->where('offer_selected', true)->pluck('id')->all());
  $priceMap = [];
  foreach ($allProducts as $p) {
      $priceMap[$p->id] = old('offer_price.'.$p->id, $p->offer_price);
  }
  $endsAtLocal = $endsAt ? \Carbon\Carbon::parse($endsAt)->timezone('America/La_Paz')->format('Y-m-d\TH:i') : '';
@endphp

<p class="form-hint" style="margin-bottom:20px;">Los productos marcados acá muestran su precio real tachado + el precio de oferta, con cuenta regresiva, en toda la tienda — sin tocar el precio real de cada producto. Apagar el switch (o que pase la fecha) hace que todo vuelva solo al precio normal.</p>

<div x-data="{
      q: '',
      selected: @js(collect($selectedIds)->map(fn ($id) => (string) $id)->all()),
      prices: @js(collect($priceMap)->mapWithKeys(fn ($v, $k) => [(string) $k => $v])->all()),
    }" style="max-width:760px;">
  <form method="POST" action="{{ route('admin.ofertas.update') }}" class="admin-form">
    @csrf
    @method('PUT')

    <div class="form-section">
      <h3>Interruptor general</h3>

      <label style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="active" value="1" {{ old('active', $active) ? 'checked' : '' }}>
        Activar ofertas
      </label>
      <div class="form-hint">Con esto apagado, todos los productos marcados abajo vuelven a mostrar su precio normal — sin borrar la selección ni los precios de oferta, por si la volvés a activar después.</div>

      <div class="form-group" style="margin-top:16px;">
        <label for="ends_at">Termina la oferta (hora de Bolivia)</label>
        <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $endsAtLocal) }}" required>
        <div class="form-hint">Misma fecha para todos los productos marcados — la cuenta regresiva de cada tarjeta cuenta hasta acá.</div>
        @error('ends_at') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Productos en oferta</h3>
      <div class="form-hint" style="margin-bottom:10px;">Desmarcar un producto lo saca de la oferta actual, pero no borra el precio que le pusiste — queda listo por si lo volvés a marcar en la próxima.</div>

      <input type="text" x-model="q" placeholder="Buscar producto..." style="margin-bottom:14px;">

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
      <div class="form-hint" style="margin-top:10px;"><span x-text="selected.length"></span> producto(s) en la oferta actual.</div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
  </form>
</div>

@endsection
