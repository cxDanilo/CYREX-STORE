@extends('admin.layout')

@section('title', 'Descuentos')
@section('page-description', 'Campañas de oferta con fecha de fin — se autolimpian solas al vencer.')

@section('content')

@php
  $allProducts = $categorizedProducts->flatten();
  $selectedIds = old('product_ids', $group ? $group->products->pluck('id')->all() : []);
  $priceMap = [];
  $offerPriceErrors = [];
  $productsForSearch = [];
  foreach ($allProducts as $p) {
      $priceMap[$p->id] = old('offer_price.'.$p->id, $p->offer_price);
      if ($errors->has('offer_price.'.$p->id)) {
          $offerPriceErrors[$p->id] = $errors->first('offer_price.'.$p->id);
      }
      $productsForSearch[] = [
          'id' => $p->id,
          'name' => $p->name,
          'category' => $p->category->name ?? 'Sin categoría',
          'price' => (float) $p->price,
          'currency' => $p->currency,
          'has_variant_override' => $p->has_variants && $p->variants->contains(fn ($v) => $v->price_override !== null),
      ];
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
      allProducts: @js($productsForSearch),
      selected: @js(collect($selectedIds)->map(fn ($id) => (string) $id)->all()),
      prices: @js(collect($priceMap)->mapWithKeys(fn ($v, $k) => [(string) $k => $v])->all()),
      errors: @js(collect($offerPriceErrors)->mapWithKeys(fn ($v, $k) => [(string) $k => $v])->all()),

      get searchResults() {
        const term = this.q.trim().toLowerCase();
        if (!term) return [];
        return this.allProducts
          .filter(p => !this.selected.includes(String(p.id)) && p.name.toLowerCase().includes(term))
          .slice(0, 8);
      },

      get selectedProducts() {
        return this.selected
          .map(id => this.allProducts.find(p => String(p.id) === String(id)))
          .filter(Boolean);
      },

      addProduct(id) {
        id = String(id);
        if (!this.selected.includes(id)) this.selected.push(id);
        this.q = '';
      },

      // No borra prices[id] a propósito — si el producto se vuelve a
      // agregar más tarde, aparece de nuevo con el mismo precio de
      // oferta en vez de tener que escribirlo de cero.
      removeProduct(id) {
        this.selected = this.selected.filter(x => x !== String(id));
      },
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
      <div class="form-hint" style="margin-bottom:10px;">Buscá un producto por nombre para agregarlo — sacarlo de acá no borra el precio de oferta que le pusiste, por si lo volvés a sumar.</div>

      <div style="position:relative;" @click.outside="q = ''">
        <input type="text" x-model="q" placeholder="Buscar producto..." class="admin-product-search" autocomplete="off">
        <div class="admin-search-results" x-show="searchResults.length" x-cloak>
          <template x-for="p in searchResults" :key="p.id">
            <button type="button" class="admin-search-result-row" @click="addProduct(p.id)">
              <span class="combo-product-row-name" x-text="p.name"></span>
              <span class="admin-search-result-price mono" x-text="(p.currency === 'USD' ? '$' : 'Bs ') + p.price.toFixed(2)"></span>
              <span class="admin-search-result-add">+ Agregar</span>
            </button>
          </template>
        </div>
        <div class="form-hint" style="margin-top:8px;" x-show="q.trim() && !searchResults.length" x-cloak>Ningún producto activo coincide con "<span x-text="q"></span>".</div>
      </div>

      <div style="margin-top:16px;" x-show="selectedProducts.length" x-cloak>
        <template x-for="p in selectedProducts" :key="p.id">
          <div class="combo-product-row offer-product-row" style="cursor:default;">
            <button type="button" class="admin-remove-btn" @click="removeProduct(p.id)" aria-label="Quitar de la campaña" title="Quitar de la campaña">×</button>
            <span class="combo-product-row-info">
              <span class="combo-product-row-name">
                <span x-text="p.name"></span>
                <span x-show="p.has_variant_override" title="Tiene variantes con precio propio — esa variante sigue cobrando su propio precio, no el de oferta.">⚠️</span>
              </span>
              <span class="combo-product-row-price mono" x-text="(p.currency === 'USD' ? '$' : 'Bs ') + p.price.toFixed(2)"></span>
            </span>
            <input type="number" step="0.01" min="0.01" class="offer-price-input mono"
                   :name="'offer_price[' + p.id + ']'"
                   x-model="prices[p.id]"
                   placeholder="Precio oferta">
            <div class="error" style="width:100%;order:99;" x-show="errors[p.id]" x-text="errors[p.id]" x-cloak></div>
          </div>
        </template>
      </div>
      <p class="form-hint" x-show="!selectedProducts.length" x-cloak>Todavía no agregaste ningún producto — buscalo arriba.</p>

      <template x-for="id in selected" :key="'hidden-'+id">
        <input type="hidden" name="product_ids[]" :value="id">
      </template>

      <div class="form-hint" style="margin-top:10px;"><span x-text="selected.length"></span> producto(s) en la campaña.</div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
  </form>
</div>

@endsection
