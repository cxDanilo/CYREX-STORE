@extends('admin.layout')

@section('title', 'Descuentos')
@section('page-description', 'Campañas de oferta con fecha de inicio y fin — se autolimpian solas al vencer.')

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
  $startsAtLocal = old('starts_at', $group ? $group->starts_at->timezone('America/La_Paz')->format('Y-m-d\TH:i') : '');
  $endsAtLocal = old('ends_at', $group ? $group->ends_at->timezone('America/La_Paz')->format('Y-m-d\TH:i') : '');
@endphp

<p class="form-hint" style="margin-bottom:20px;">Los productos que sumes acá muestran su precio real tachado + el precio de oferta, con cuenta regresiva, en toda la tienda — sin tocar el precio real de cada producto. Al llegar la fecha de fin, la campaña se borra sola y todos vuelven a su precio normal (no hace falta apagar nada a mano).</p>

<div x-data="{
      creating: {{ $group || $errors->any() ? 'true' : 'false' }},
      detailsOpen: {{ !$group || $errors->any() ? 'true' : 'false' }},
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

  {{-- Sin campaña todavía: un botón nomás, nada de formulario ocupando
       pantalla — recién al tocarlo aparecen nombre/fechas. Sin
       productos en este paso a propósito: la campaña se crea primero
       (POST a store(), sin id todavía) y los productos se agregan
       después, ya con la campaña creada. --}}
  <div x-show="!creating" x-cloak>
    <button type="button" class="btn btn-primary" @click="creating = true">+ Nueva campaña</button>
  </div>

  <form method="POST" action="{{ $group ? route('admin.descuentos.update', $group) : route('admin.descuentos.store') }}" class="admin-form" x-show="creating" x-cloak>
    @csrf
    @if($group)
      @method('PUT')
    @endif

    <div class="form-section">
      @if($group)
        {{-- Campaña ya creada: resumen compacto por default (nombre +
             rango de fechas), con un link para desplegar los campos
             si hace falta corregir algo — así no vuelve a ocupar toda
             la pantalla cada vez que se entra a sumar productos. --}}
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;" x-show="!detailsOpen">
          <div>
            <h3 style="margin-bottom:4px;">{{ $group->name }}</h3>
            <div class="form-hint" style="margin-bottom:0;">{{ $group->starts_at->timezone('America/La_Paz')->format('d/m/Y H:i') }} → {{ $group->ends_at->timezone('America/La_Paz')->format('d/m/Y H:i') }} (hora de Bolivia)</div>
          </div>
          <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="button" class="btn btn-sm" @click="detailsOpen = true">Editar nombre/fechas</button>
            <button type="submit" form="end-campaign-form" class="btn btn-danger btn-sm">Terminar campaña ahora</button>
          </div>
        </div>
        <div x-show="detailsOpen" x-cloak>
      @else
          <h3>Nueva campaña</h3>
      @endif

          <div class="form-group">
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" value="{{ old('name', $group->name ?? '') }}" placeholder="Ej. Días Amarillos" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
          </div>

          <div class="form-group" style="margin-top:16px;">
            <label for="starts_at">Empieza (hora de Bolivia)</label>
            <input type="datetime-local" id="starts_at" name="starts_at" value="{{ $startsAtLocal }}" required>
            <div class="form-hint">Podés programarla para más adelante — no se muestra ningún precio de oferta hasta esta fecha.</div>
            @error('starts_at') <div class="error">{{ $message }}</div> @enderror
          </div>

          <div class="form-group" style="margin-top:16px;">
            <label for="ends_at">Termina (hora de Bolivia)</label>
            <input type="datetime-local" id="ends_at" name="ends_at" value="{{ $endsAtLocal }}" required>
            <div class="form-hint">Misma fecha para todos los productos de la campaña — la cuenta regresiva de cada tarjeta cuenta hasta acá.</div>
            @error('ends_at') <div class="error">{{ $message }}</div> @enderror
          </div>

      @if($group)
          <div style="margin-top:16px;">
            <button type="button" class="btn btn-sm" @click="detailsOpen = false">Listo, ocultar</button>
          </div>
        </div>
      @else
        <div style="margin-top:16px;">
          <button type="button" class="btn" @click="creating = false">Cancelar</button>
        </div>
      @endif
    </div>

    @if($group)
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
    @endif

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">{{ $group ? 'Guardar' : 'Crear campaña y agregar productos' }}</button>
    </div>
  </form>

  @if($group)
    {{-- Sin botón propio a propósito — el botón real vive arriba, en el
         resumen compacto de la campaña (@click en un <button form="...">
         referencia este <form> por id, ya que no puede anidarse dentro
         del <form> principal de más arriba). --}}
    <form id="end-campaign-form" method="POST" action="{{ route('admin.descuentos.destroy', $group) }}" onsubmit="return confirm('¿Terminar la campaña «{{ $group->name }}» ahora? Los productos vuelven a su precio normal de inmediato.');" style="display:none;">
      @csrf
      @method('DELETE')
    </form>
  @endif
</div>

@endsection
