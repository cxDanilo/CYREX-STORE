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
          'image_url' => $p->image_thumb_url,
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
      activeIndex: 0,
      previewId: null,
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

      get previewProduct() {
        return this.previewId ? this.allProducts.find(p => String(p.id) === String(this.previewId)) : null;
      },

      // Con búsqueda de a un producto por vez alcanzaba con un botón +
      // Agregar por resultado — pero pensado para cargar 50-100 de
      // corrido, cada click de mouse de más pesa. Con las flechas +
      // Enter se agrega sin soltar el teclado nunca.
      pickResult(i) {
        if (i < 0 || i >= this.searchResults.length) return;
        this.addProduct(this.searchResults[i].id);
      },

      addProduct(id) {
        id = String(id);
        if (!this.selected.includes(id)) this.selected.push(id);
        this.q = '';
        this.activeIndex = 0;
        // Foco directo al precio de la fila recién agregada — así se
        // seguí escribiendo sin ir a buscarla con el mouse.
        this.$nextTick(() => {
          const el = this.$el.querySelector('[data-price-for=\'' + id + '\']');
          if (el) el.focus();
        });
      },

      // No borra prices[id] a propósito — si el producto se vuelve a
      // agregar más tarde, aparece de nuevo con el mismo precio de
      // oferta en vez de tener que escribirlo de cero.
      removeProduct(id) {
        this.selected = this.selected.filter(x => x !== String(id));
        if (String(this.previewId) === String(id)) this.previewId = null;
      },

      discountPercent(p) {
        const offer = parseFloat(this.prices[p.id]);
        if (!offer || offer >= p.price) return null;
        return Math.round((1 - offer / p.price) * 100);
      },
    }" style="max-width:1400px;">

  {{-- Sin campaña todavía: un botón nomás, nada de formulario ocupando
       pantalla — recién al tocarlo aparecen nombre/fechas. Sin
       productos en este paso a propósito: la campaña se crea primero
       (POST a store(), sin id todavía) y los productos se agregan
       después, ya con la campaña creada. --}}
  <div x-show="!creating" x-cloak>
    <button type="button" class="btn btn-primary" @click="creating = true">+ Nueva campaña</button>
  </div>

  {{-- Sin class="admin-form": esa clase trae un layout multi-columna
       (columns:420px, pensado para formularios con muchos campos
       chicos tipo Productos) que forzaba la sección de productos a un
       solo "carril" angosto sin importar cuánto se ensanche el
       contenedor de afuera — acá se necesita ancho completo de verdad. --}}
  <form method="POST" action="{{ $group ? route('admin.descuentos.update', $group) : route('admin.descuentos.store') }}" x-show="creating" x-cloak>
    @csrf
    @if($group)
      @method('PUT')
    @endif

    <div class="form-section" style="max-width:620px;">
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
        <div class="form-hint" style="margin-bottom:10px;">Buscá, elegí con <kbd>↑</kbd><kbd>↓</kbd> + <kbd>Enter</kbd>, escribí el precio y <kbd>Enter</kbd> de nuevo vuelve acá para el siguiente — pensado para cargar muchos de corrido sin soltar el teclado.</div>

        <div style="position:relative;" @click.outside="q = ''">
          <input type="text" x-model="q" x-ref="searchInput" placeholder="Buscar producto..." autocomplete="off" class="admin-product-search"
                 @input="activeIndex = 0"
                 @keydown.arrow-down.prevent="activeIndex = Math.min(activeIndex + 1, searchResults.length - 1)"
                 @keydown.arrow-up.prevent="activeIndex = Math.max(activeIndex - 1, 0)"
                 @keydown.enter.prevent="pickResult(activeIndex)"
                 @keydown.escape="q = ''">
          <div class="admin-search-results" x-show="searchResults.length" x-cloak>
            <template x-for="(p, i) in searchResults" :key="p.id">
              <button type="button" class="admin-search-result-row" :class="{ 'is-active': i === activeIndex }" @mouseenter="activeIndex = i" @click="addProduct(p.id)">
                <span class="combo-product-row-name" x-text="p.name"></span>
                <span class="admin-search-result-price mono" x-text="(p.currency === 'USD' ? '$' : 'Bs ') + p.price.toFixed(2)"></span>
              </button>
            </template>
          </div>
          <div class="form-hint" style="margin-top:8px;" x-show="q.trim() && !searchResults.length" x-cloak>Ningún producto activo coincide con "<span x-text="q"></span>".</div>
        </div>

        <div style="overflow-x:auto;" x-show="selectedProducts.length" x-cloak>
          <table class="admin-discount-table">
            <colgroup>
              <col style="width:56px;">
              <col>
              <col style="width:110px;">
              <col style="width:130px;">
              <col style="width:64px;">
              <col style="width:44px;">
            </colgroup>
            <thead>
              <tr><th></th><th>Producto</th><th>Precio actual</th><th>Precio oferta</th><th>%</th><th></th></tr>
            </thead>
            <tbody>
              <template x-for="p in selectedProducts" :key="p.id">
                <tr>
                  <td>
                    <div class="admin-discount-thumb">
                      <img :src="p.image_url" x-show="p.image_url" loading="lazy">
                    </div>
                  </td>
                  <td class="admin-discount-name">
                    <span x-text="p.name"></span>
                    <span x-show="p.has_variant_override" title="Tiene variantes con precio propio — esa variante sigue cobrando su propio precio, no el de oferta.">⚠️</span>
                    <span class="admin-discount-cat" x-text="p.category"></span>
                  </td>
                  <td class="mono" style="color:var(--text-muted);white-space:nowrap;" x-text="(p.currency === 'USD' ? '$' : 'Bs ') + p.price.toFixed(2)"></td>
                  <td>
                    <input type="number" step="0.01" min="0.01" class="offer-price-input mono"
                           :name="'offer_price[' + p.id + ']'"
                           :data-price-for="p.id"
                           x-model="prices[p.id]"
                           placeholder="Precio"
                           @focus="previewId = p.id"
                           @blur="previewId = (previewId === p.id) ? null : previewId"
                           @keydown.enter.prevent="$refs.searchInput.focus()">
                    <div class="error" x-show="errors[p.id]" x-text="errors[p.id]" x-cloak></div>
                  </td>
                  <td class="mono admin-discount-pct" :class="{ 'is-empty': !discountPercent(p) }" x-text="discountPercent(p) ? ('-' + discountPercent(p) + '%') : '—'"></td>
                  <td><button type="button" class="admin-remove-btn" @click="removeProduct(p.id)" aria-label="Quitar de la campaña" title="Quitar de la campaña">×</button></td>
                </tr>
              </template>
            </tbody>
          </table>
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

  {{-- Vista previa flotante: con potencialmente 50-100 productos
       cargados, mostrar TODAS las cards de una (como se hacía antes)
       no escala — se ve solo la del campo de precio que se está
       tocando en este momento, mismas clases CSS que la tarjeta real
       de la tienda. --}}
  <div class="admin-discount-preview" x-show="previewProduct" x-cloak x-transition.opacity.duration.150ms>
    <template x-if="previewProduct">
      <div class="card" style="pointer-events:none;">
        <div class="card-media">
          <img :src="previewProduct.image_url" x-show="previewProduct.image_url" style="opacity:1;">
          <div class="card-badges">
            <span class="card-badge-promo" x-show="discountPercent(previewProduct)" x-text="'-' + discountPercent(previewProduct) + '%'"></span>
          </div>
        </div>
        <div class="card-body">
          <div class="card-cat" x-text="previewProduct.category"></div>
          <div class="card-name" x-text="previewProduct.name"></div>
          <div class="card-price-original" x-show="discountPercent(previewProduct)" x-text="(previewProduct.currency === 'USD' ? '$' : 'Bs ') + previewProduct.price.toFixed(2)"></div>
          <div class="card-price" x-text="(previewProduct.currency === 'USD' ? '$' : 'Bs ') + (discountPercent(previewProduct) ? parseFloat(prices[previewProduct.id]) : previewProduct.price).toFixed(2)"></div>
        </div>
      </div>
    </template>
  </div>
</div>

@endsection
