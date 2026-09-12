@extends('layouts.app')

@section('title', $product->name . ' — Cyrex Store')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 160) ?: $product->name . ' — disponible en Cyrex Store.')
@section('og_image', $product->image_url ?? asset('images/logo-horizontal.png'))
@section('og_type', 'product')

@section('content')

@php
  $showBobInitial = $forceBob || $currencyMode === 'bob_only' || ($currencyMode === 'both' && $defaultCurrency === 'BOB');
  $hasOffer = $product->hasActiveOffer();
  $realPriceUsd = $product->currency === 'USD' ? (float) $product->price : (float) $product->price / $rate;
  $basePriceUsd = $product->currency === 'USD' ? $product->effectivePrice() : $product->effectivePrice() / $rate;
  $priceMainInitial = $showBobInitial
      ? 'Bs '.number_format($basePriceUsd * $rate, 2)
      : '$'.number_format($basePriceUsd, 2);
  $priceAltInitial = $showBobInitial
      ? '≈ $'.number_format($basePriceUsd, 2).' USD'
      : '≈ Bs '.number_format($basePriceUsd * $rate, 2);
  $originalPriceMainInitial = $showBobInitial
      ? 'Bs '.number_format($realPriceUsd * $rate, 2)
      : '$'.number_format($realPriceUsd, 2);
  $whatsappNumber = \App\Support\ReferralRouter::whatsappNumber();
  $productUrl = route('product.show', $product->slug);

  // Para piezas de PC, los datos de "Compatibilidad" (socket, tipo de
  // RAM, etc.) ya se cargan una vez para que los use el armador — en vez
  // de pedirle al admin que los vuelva a escribir a mano en
  // "Especificaciones técnicas", se muestran acá directo, traducidos a
  // su label legible (y con las opciones de listas dinámicas ya
  // resueltas: Admin → Compatibilidad).
  $compatDisplay = [];
  if ($product->component_type && $product->compat) {
      $fieldsDef = \App\Support\PcBuilderFields::resolved()[$product->component_type] ?? [];
      foreach ($fieldsDef as $key => $field) {
          $value = $product->compat[$key] ?? null;
          if ($value === null || $value === '') {
              continue;
          }
          if ($field['type'] === 'checkboxes') {
              $compatDisplay[$field['label']] = collect((array) $value)
                  ->map(fn ($v) => $field['options'][$v] ?? $v)
                  ->implode(', ');
          } elseif ($field['type'] === 'select') {
              $compatDisplay[$field['label']] = $field['options'][$value] ?? $value;
          } else {
              $compatDisplay[$field['label']] = $value;
          }
      }
  }
  $displaySpecs = $compatDisplay + ($product->specs ?? []);
@endphp

{{-- Datos estructurados (schema.org) — sin esto Google no tiene forma de
     mostrar precio/disponibilidad directo en el resultado de búsqueda
     (los "rich snippets"). No cambia nada visible en la página. --}}
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'Product',
  'name' => $product->name,
  'image' => $product->image_url,
  'description' => strip_tags($product->description ?? $product->name),
  'sku' => (string) $product->id,
  ...($product->brand ? ['brand' => ['@type' => 'Brand', 'name' => $product->brand]] : []),
  'offers' => [
    '@type' => 'Offer',
    'url' => $productUrl,
    'priceCurrency' => $product->currency,
    'price' => number_format($product->effectivePrice(), 2, '.', ''),
    'availability' => $product->is_sold_out
      ? 'https://schema.org/OutOfStock'
      : 'https://schema.org/InStock',
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / <a href="{{ route('shop', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a> / {{ $product->name }}
</div>

<div class="wrap product-hero"
     x-data="{
        variant: {{ $product->variants->first()?->id ?? 'null' }},
        showBob: {{ $showBobInitial ? 'true' : 'false' }},
        toggled: false,
        rate: {{ $rate }},
        // basePrice era un valor fijo calculado una sola vez en el
        // servidor — nunca se actualizaba al cambiar de variante. Ahora
        // es un getter: cada variante puede traer su propio
        // price_override (ver Cart.php, que YA lo respeta al agregar al
        // carrito — acá solo faltaba reflejarlo en la pantalla).
        get basePrice() {
          const v = this.variants.find(v => v.id === this.variant);
          // effectivePrice (no originalPrice) es a propósito: originalPrice
          // queda reservado para la edición rápida del precio REAL — acá
          // se muestra el precio de oferta si hay una activa, salvo que la
          // variante elegida tenga su propio price_override, que sigue
          // ganando por encima de la oferta igual que ya ganaba antes.
          const raw = (v && v.price_override !== null && v.price_override !== undefined) ? v.price_override : this.effectivePrice;
          return this.editCurrency === 'USD' ? raw : raw / this.rate;
        },
        hasOffer: {{ $hasOffer ? 'true' : 'false' }},
        effectivePrice: {{ $product->effectivePrice() }},
        realPriceUsd: {{ $realPriceUsd }},
        // La variante override sigue ganando por encima de la oferta (ver
        // basePrice) — mostrar el tachado + badge + contador ahí sería
        // confuso, porque esa variante puntual ya no cobra el precio de
        // oferta en absoluto.
        get variantHasOverride() {
          const v = this.variants.find(v => v.id === this.variant);
          return !!(v && v.price_override !== null && v.price_override !== undefined);
        },
        variants: {{ $product->variants->map(fn($v) => ['id' => $v->id, 'name' => $v->variant_value, 'price_override' => $v->price_override !== null ? (float) $v->price_override : null, 'image' => $v->image_url, 'is_sold_out' => $v->is_sold_out])->toJson() }},
        productSoldOut: {{ $product->is_sold_out ? 'true' : 'false' }},
        // Agotado si el producto entero lo está, O si la variante
        // elegida puntualmente lo está (ej. Rojo agotado pero Negro
        // todavía disponible) — antes era un valor fijo que nunca
        // reaccionaba a cuál variante estuviera elegida.
        //
        // Iba a ser un getter (como mainImage/basePrice de acá arriba)
        // pero el x-show del pill de Agotado no reaccionaba a él (sí
        // reaccionaban :disabled y el texto del botón, que dependen del
        // mismo getter) — un plain property actualizado a mano en cada
        // cambio de variante, en cambio, sí funciona en todos lados,
        // así que se deja así. [Nota: nunca escribas el caracter de
        // comilla doble suelto en un comentario acá adentro — x-data
        // va dentro de un atributo HTML delimitado con ese mismo
        // caracter, así que uno solo de más corta el atributo a la
        // mitad. Usá comillas simples o directamente ninguna.]
        inStock: {{ ($product->is_sold_out || ($product->variants->first()?->is_sold_out ?? false)) ? 'false' : 'true' }},
        updateInStock() {
          if (this.productSoldOut) { this.inStock = false; return; }
          const v = this.variants.find(v => v.id === this.variant);
          this.inStock = !(v && v.is_sold_out);
        },
        flashPrice() {
          this.$root.querySelectorAll('.price-main, .price-alt').forEach(el => {
            el.classList.remove('price-flash');
            void el.offsetWidth;
            el.classList.add('price-flash');
          });
        },
        // Antes el toggle USD/BOB (y el cambio de variante) pisaba el
        // número de golpe (x-text) — se sentía brusco. syncPrice
        // reemplaza al x-text: la primera vez (montaje del componente,
        // mismo texto que ya vino renderizado del servidor) solo marca
        // el elemento como listo, sin animar nada; de ahí en más, cada
        // cambio dispara el carrete (ver reelPrice).
        syncPrice(el, target, prefix, suffix) {
          const span = el.querySelector('.price-text');
          const text = prefix + target.toFixed(2) + suffix;
          if (!span.dataset.primed) {
            span.textContent = text;
            span.dataset.primed = '1';
            span.dataset.current = text;
            return;
          }
          this.reelPrice(span, text);
        },
        // Cada carácter que cambia de valor (no todos, solo los que
        // difieren entre el precio viejo y el nuevo) se arma con dos
        // fotogramas apilados adentro de una celda recortada — el viejo
        // arriba, el nuevo ya esperando debajo — y un translateY los
        // desliza para revelar el de abajo, como el contador de un
        // instrumento de precisión. Con demora creciente por posición
        // (34ms cada uno) para que no se sientan todos pegados, y sin
        // tocar los caracteres que se repiten entre un valor y el otro.
        reelPrice(span, newText) {
          // El valor real actual se guarda aparte en vez de leerlo de
          // textContent: apenas termina un carrete, la celda se queda
          // con los DOS fotogramas todavía en el DOM (nada más que el
          // de abajo se ve, por el recorte) — leer textContent ahí
          // devolvería una mezcla de ambos, y el próximo cambio
          // compararía caracteres contra ese texto mezclado.
          const oldText = span.dataset.current || span.textContent;
          if (oldText === newText) return;
          const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.body.classList.contains('motion-reduced');
          if (reduced) {
            span.textContent = newText;
            span.dataset.current = newText;
            return;
          }

          const maxLen = Math.max(oldText.length, newText.length);
          span.innerHTML = '';
          const cells = [];
          for (let i = 0; i < maxLen; i++) {
            const oldCh = oldText[i] || '';
            const newCh = newText[i] || '';
            if (oldCh === newCh) {
              const plain = document.createElement('span');
              plain.className = 'char-cell';
              plain.textContent = oldCh;
              span.appendChild(plain);
              continue;
            }
            const cell = document.createElement('span');
            cell.className = 'char-cell reel-cell';
            const track = document.createElement('span');
            track.className = 'reel-track';
            const frameOld = document.createElement('span');
            frameOld.className = 'reel-frame';
            frameOld.textContent = oldCh;
            const frameNew = document.createElement('span');
            frameNew.className = 'reel-frame';
            frameNew.textContent = newCh;
            track.append(frameOld, frameNew);
            cell.appendChild(track);
            span.appendChild(cell);
            cells.push(cell);
          }
          void span.offsetWidth;

          cells.forEach((cell, i) => {
            setTimeout(() => cell.classList.add('is-reeling'), i * 34);
          });

          span.dataset.current = newText;
        },
        whatsappHref() {
          const variantObj = this.variants.find(v => v.id === this.variant);
          const variantSuffix = variantObj ? ' (' + variantObj.name + ')' : '';
          const priceUsd = this.basePrice.toFixed(2);
          const priceBob = (this.basePrice * this.rate).toFixed(2);
          const text = 'Hola! Me interesa este producto:\n'
            + {{ Js::from($product->name) }} + variantSuffix + '\n'
            + '$' + priceUsd + ' USD / Bs ' + priceBob + ' BOB\n'
            + {{ Js::from($productUrl) }};
          return 'https://wa.me/{{ $whatsappNumber }}?text=' + encodeURIComponent(text);
        },
        isAdmin: {{ (auth()->check() && auth()->user()->isAdmin()) ? 'true' : 'false' }},
        editing: false,
        saving: false,
        imageSwapTimer: null,
        // Antes esto solo esperaba los 180ms del fade y recién ahí
        // pisaba el src — pero cambiar el src no hace que la foto
        // aparezca al instante, el navegador todavía tiene que
        // bajarla de la red. Si tardaba (sobre todo la primera vez
        // que se pedía esa foto), se veía la imagen vieja/en blanco
        // un momento y la nueva 'aparecía de golpe' — el mismo
        // parpadeo de siempre, con otra causa. Precargando la foto
        // en un <img> aparte y esperando a que esa SÍ termine de
        // cargar (o falle) antes de recién ahí pisar el src visible,
        // nunca se muestra nada a medio cargar.
        swapMainImage(el, url) {
          clearTimeout(this.imageSwapTimer);
          el.classList.add('img-fading');
          this.imageSwapTimer = setTimeout(() => {
            const preload = new Image();
            const apply = () => { el.src = url; el.classList.remove('img-fading'); };
            preload.onload = apply;
            preload.onerror = apply;
            preload.src = url;
            if (preload.complete) apply();
          }, 180);
        },
        editName: {{ Js::from($product->name) }},
        editPrice: {{ (float) $product->price }},
        editCurrency: {{ Js::from($product->currency) }},
        editDescription: {{ Js::from($product->description ?? '') }},
        editImageUrl: {{ Js::from($product->image_url) }},
        editImagePreview: null,
        editOfferSelected: {{ $product->offer_selected ? 'true' : 'false' }},
        editOfferPrice: {{ Js::from($product->offer_price !== null ? (string) $product->offer_price : '') }},
        editEndsAt: '',
        initialOfferSelected: {{ $product->offer_selected ? 'true' : 'false' }},
        initialOfferPrice: {{ Js::from($product->offer_price !== null ? (string) $product->offer_price : '') }},
        activeGroupName: {{ Js::from($activeDiscountGroup?->name) }},
        activeGroupEndsAtLabel: {{ Js::from($activeDiscountGroup ? $activeDiscountGroup->ends_at->timezone('America/La_Paz')->format('d/m/Y H:i') : null) }},
        offerError: null,
        galleryImages: {{ Js::from($product->gallery_urls) }},
        galleryActive: null,
        get selectedVariantImage() {
          const v = this.variants.find(v => v.id === this.variant);
          return v ? v.image : null;
        },
        // galleryActive (una miniatura clickeada a mano) va ANTES que
        // selectedVariantImage a propósito: si fuera al revés, con una
        // variante-con-foto elegida, clickear una miniatura de la
        // galería no haría nada (quedaría tapado siempre por la foto
        // de la variante). El init() de abajo limpia galleryActive al
        // cambiar de variante, para que la foto de la variante nueva
        // se vea sin tener que tocar nada más.
        get mainImage() { return this.editImagePreview || this.galleryActive || this.selectedVariantImage || this.editImageUrl || this.galleryImages[0] || null; },
        init() {
          this.$watch('variant', () => { this.galleryActive = null; this.updateInStock(); });
        },
        originalName: {{ Js::from($product->name) }},
        originalPrice: {{ (float) $product->price }},
        originalDescription: {{ Js::from($product->description ?? '') }},
        quickUpdateUrl: {{ Js::from(route('product.quick-update', $product)) }},
        toggleEdit() {
          if (this.editing) {
            this.cancelEdit();
          } else {
            this.editing = true;
          }
        },
        cancelEdit() {
          this.editName = this.originalName;
          this.editPrice = this.originalPrice;
          this.editDescription = this.originalDescription;
          this.editImagePreview = null;
          this.editOfferSelected = this.initialOfferSelected;
          this.editOfferPrice = this.initialOfferPrice;
          this.editEndsAt = '';
          this.offerError = null;
          if (this.$refs.quickEditImage) this.$refs.quickEditImage.value = '';
          this.editing = false;
        },
        onEditImagePicked(event) {
          const file = event.target.files[0];
          this.editImagePreview = file ? URL.createObjectURL(file) : null;
        },
        async saveQuickEdit() {
          this.saving = true;
          this.offerError = null;

          // La oferta toca varias cosas que no son un simple valor
          // reactivo (el contador regresivo compartido de nav.blade.php
          // arranca una sola vez al cargar la página, el % del badge
          // viene armado del servidor) — más simple y confiable
          // recargar la página cuando de verdad cambió algo de la
          // oferta, en vez de tratar de sincronizar cada pieza a mano.
          const offerChanged = this.editOfferSelected !== this.initialOfferSelected
            || (this.editOfferSelected && String(this.editOfferPrice) !== String(this.initialOfferPrice));

          const formData = new FormData();
          formData.append('_method', 'PATCH');
          formData.append('name', this.editName);
          formData.append('price', this.editPrice);
          formData.append('description', this.editDescription ?? '');
          formData.append('offer_selected', this.editOfferSelected ? '1' : '0');
          if (this.editOfferSelected) {
            formData.append('offer_price', this.editOfferPrice ?? '');
            if (!this.activeGroupName) formData.append('ends_at', this.editEndsAt ?? '');
          }
          const fileInput = this.$refs.quickEditImage;
          if (fileInput && fileInput.files[0]) {
            formData.append('image', fileInput.files[0]);
          }
          try {
            const res = await fetch(this.quickUpdateUrl, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
              },
              body: formData,
            });
            if (res.status === 422) {
              const body = await res.json();
              this.offerError = Object.values(body.errors || {}).flat()[0] || 'Revisá los datos de la oferta.';
              return;
            }
            if (!res.ok) throw new Error('quick edit failed');

            if (offerChanged) {
              location.reload();
              return;
            }

            const data = await res.json();
            this.editName = this.originalName = data.name;
            this.editPrice = this.originalPrice = data.price;
            // Si hay una oferta activa, lo que se muestra sigue siendo el
            // precio de oferta (lo maneja Admin → Descuentos) — solo se
            // refleja el precio nuevo acá cuando no hay ninguna oferta
            // pisándolo.
            if (!this.hasOffer) { this.effectivePrice = data.price; }
            this.editDescription = this.originalDescription = data.description ?? '';
            this.editImageUrl = data.image_url;
            this.editImagePreview = null;
            if (this.$refs.quickEditImage) this.$refs.quickEditImage.value = '';
            this.editing = false;
          } catch (e) {
            alert('No se pudo guardar el cambio. Prueba de nuevo.');
          } finally {
            this.saving = false;
          }
        }
     }">
  <div class="gallery">
    <div class="gallery-main">
      <img src="{{ $product->image_url }}" x-show="mainImage" alt="{{ $product->name }}"
           class="gallery-main-img"
           x-init="if (mainImage) $el.src = mainImage;"
           x-effect="if (mainImage && $el.src !== mainImage) swapMainImage($el, mainImage)"
           style="width:100%;height:100%;object-fit:cover;border-radius:20px;{{ $product->is_sold_out ? 'filter:grayscale(1);' : '' }}">
      <template x-if="isAdmin && editing">
        <label class="admin-edit-image-overlay">
          <span>Cambiar imagen</span>
          <input type="file" x-ref="quickEditImage" accept="image/png,image/jpeg,image/webp" @change="onEditImagePicked($event)" style="display:none;">
        </label>
      </template>
    </div>
    <div class="gallery-thumbs" x-show="galleryImages.length > 1" x-cloak>
      <template x-for="url in galleryImages" :key="url">
        <button type="button" class="gallery-thumb" :class="mainImage === url && 'active'" @click="galleryActive = url">
          <img :src="url" alt="">
        </button>
      </template>
    </div>
  </div>

  <div class="product-info">
    <div class="admin-toggle-row">
      <template x-if="isAdmin">
        <a href="{{ route('admin.productos.edit', $product) }}?back={{ urlencode(url()->current()) }}" class="admin-edit-toggle" data-no-ajax>✏️ Editar producto</a>
      </template>
      <template x-if="isAdmin">
        <button type="button" class="admin-edit-toggle" :class="editing && 'is-active'" @click="toggleEdit()" x-text="editing ? 'Cancelar edición' : '⚡ Edición rápida'"></button>
      </template>
      <template x-if="isAdmin">
        <form method="POST" action="{{ route('admin.productos.toggle-status', $product) }}">
          @csrf
          @method('PATCH')
          <button type="submit" class="admin-edit-toggle">{{ $product->status === 'active' ? '🔒 Poner en privado' : '🌐 Publicar' }}</button>
        </form>
      </template>
    </div>

    <div class="cat-eyebrow">{{ $product->category->name }}</div>
    <h1 x-show="!editing" x-text="editName">{{ $product->name }}</h1>
    <template x-if="isAdmin">
      <input type="text" x-show="editing" x-cloak x-model="editName" class="admin-edit-input admin-edit-input-name" x-transition>
    </template>

    @if($product->has_variants && $product->variants->count())
      <div class="variant-options">
        @foreach($product->variants as $v)
          <div class="variant-swatch{{ $v->is_sold_out ? ' out-of-stock' : '' }}"
               :class="{ selected: variant === {{ $v->id }} }"
               @if(!$v->is_sold_out) @click="variant = {{ $v->id }}; flashPrice()" @endif
               title="{{ $v->is_sold_out ? 'Agotada' : '' }}">
            {{ $v->variant_value }}
          </div>
        @endforeach
      </div>
    @endif

    <template x-if="isAdmin">
      <div class="admin-edit-price-row" x-show="editing" x-cloak x-transition>
        <input type="number" step="0.01" min="0" x-model.number="editPrice" class="admin-edit-input">
        <span class="admin-edit-currency-label" x-text="editCurrency"></span>
      </div>
    </template>

    <template x-if="isAdmin">
      <div x-show="editing" x-cloak x-transition>
        <label class="admin-edit-switch">
          <input type="checkbox" x-model="editOfferSelected">
          <span class="admin-edit-switch-track"></span>
          <span class="admin-edit-switch-label">En oferta</span>
        </label>

        <div class="admin-edit-offer-fields" x-show="editOfferSelected" x-cloak>
          <input type="number" step="0.01" min="0.01" x-model="editOfferPrice" class="admin-edit-input" placeholder="Precio de oferta">
          <template x-if="activeGroupName">
            <span class="admin-edit-offer-hint" x-text="'Termina junto con «' + activeGroupName + '» el ' + activeGroupEndsAtLabel"></span>
          </template>
          <template x-if="!activeGroupName">
            <div class="admin-edit-datepicker" x-data="offerDatePicker()" @click.outside="open = false">
              <button type="button" class="admin-edit-input admin-edit-datepicker-trigger" :class="{ 'is-placeholder': !editEndsAt }" @click="open = !open" x-text="displayLabel"></button>
              <div class="admin-edit-datepicker-panel" x-show="open" x-cloak x-transition>
                <div class="admin-edit-datepicker-nav">
                  <button type="button" @click="prevMonth()">‹</button>
                  <span x-text="monthLabel"></span>
                  <button type="button" @click="nextMonth()">›</button>
                </div>
                <div class="admin-edit-datepicker-weekdays">
                  <template x-for="w in weekdays" :key="w"><span x-text="w"></span></template>
                </div>
                <div class="admin-edit-datepicker-days">
                  <template x-for="(day, i) in days" :key="i">
                    <button type="button"
                      class="admin-edit-datepicker-day"
                      :class="{ 'is-empty': !day, 'is-selected': isSelected(day), 'is-today': isToday(day) }"
                      :disabled="!day"
                      @click="pickDay(day)"
                      x-text="day || ''"></button>
                  </template>
                </div>
                <div class="admin-edit-datepicker-time">
                  <select x-model="selHour" @change="applyTime()">
                    <template x-for="h in 24" :key="h"><option :value="String(h - 1).padStart(2, '0')" x-text="String(h - 1).padStart(2, '0')"></option></template>
                  </select>
                  <span>:</span>
                  <select x-model="selMinute" @change="applyTime()">
                    <template x-for="m in [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55]" :key="m"><option :value="String(m).padStart(2, '0')" x-text="String(m).padStart(2, '0')"></option></template>
                  </select>
                </div>
                <div class="admin-edit-datepicker-actions">
                  <button type="button" class="admin-edit-datepicker-link" @click="setToday()">Hoy</button>
                  <button type="button" class="admin-edit-datepicker-link" @click="clear()">Borrar</button>
                </div>
              </div>
            </div>
          </template>
        </div>
        <template x-if="offerError">
          <div class="admin-edit-error" x-text="offerError"></div>
        </template>
      </div>
    </template>

    <div class="price-block" x-show="!editing">
      @if($currencyMode === 'both')
        <div class="currency-toggle" style="margin-bottom:14px;">
          <div class="toggle-thumb" :class="toggled ? (showBob ? 'to-bob' : 'to-usd') : (showBob ? 'at-bob' : 'at-usd')"></div>
          <button type="button" @click="toggled = true; showBob = false" :class="!showBob && 'active'">USD</button>
          <button type="button" @click="toggled = true; showBob = true" :class="showBob && 'active'">BOB</button>
        </div>
      @endif
      <div x-show="hasOffer && !variantHasOverride" x-cloak style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
        <span class="card-badge-promo">-{{ $product->offerDiscountPercent() }}% OFERTA</span>
      </div>
      <div class="price-original" x-show="hasOffer && !variantHasOverride" x-cloak
           x-effect="syncPrice($el, showBob ? realPriceUsd * rate : realPriceUsd, showBob ? 'Bs ' : '$', '')"><span class="price-text">{{ $originalPriceMainInitial }}</span></div>
      <div class="price-main"
           x-effect="syncPrice($el, showBob ? basePrice * rate : basePrice, showBob ? 'Bs ' : '$', '')"><span class="price-text">{{ $priceMainInitial }}</span></div>
      @if($currencyMode === 'both')
        <div class="price-alt"
             x-effect="syncPrice($el, showBob ? basePrice : basePrice * rate, showBob ? '≈ $' : '≈ Bs ', showBob ? ' USD' : '')"><span class="price-text">{{ $priceAltInitial }}</span></div>
      @endif
      <div class="offer-countdown" x-show="hasOffer && !variantHasOverride" x-cloak>
        <span class="offer-countdown-label">⏳ Termina en</span>
        <div class="offer-countdown-digits">
          <div class="offer-countdown-seg"><span x-text="$store.offer.d"></span><small>días</small></div>
          <span class="offer-countdown-colon">:</span>
          <div class="offer-countdown-seg"><span x-text="$store.offer.h"></span><small>hrs</small></div>
          <span class="offer-countdown-colon">:</span>
          <div class="offer-countdown-seg"><span x-text="$store.offer.m"></span><small>min</small></div>
          <span class="offer-countdown-colon">:</span>
          <div class="offer-countdown-seg"><span x-text="$store.offer.s"></span><small>seg</small></div>
        </div>
      </div>
    </div>

    {{-- :class en vez de x-show -- x-show (con x-cloak) no reaccionaba
         acá al cambiar de variante (sí reaccionaban :disabled y el
         texto del botón de más abajo, atados al mismo inStock) --
         :class usa el mismo mecanismo de binding que :disabled, que sí
         funciona bien. --}}
    <div class="stock-pill out-of-stock" :class="{ 'is-hidden': editing || inStock }" x-cloak>✕ Agotado</div>

    <div class="btn-cta-row">
      <button type="button" class="btn-cta"
              :class="$store.cart.has({{ $product->id }}, variant) && 'in-cart'"
              :disabled="!inStock"
              @click="
                if ($store.cart.has({{ $product->id }}, variant)) {
                  $store.cart.remove({{ $product->id }} + ':' + (variant ?? ''));
                } else {
                  await $store.cart.add({{ $product->id }}, variant);
                  $el.classList.remove('btn-cta-added');
                  void $el.offsetWidth;
                  $el.classList.add('btn-cta-added');
                }
              ">
        <span x-text="!inStock ? 'Agotado' : ($store.cart.has({{ $product->id }}, variant) ? 'En el carrito ✓' : 'Agregar al carrito')">Agregar al carrito</span>
      </button>

      <a :href="whatsappHref()" target="_blank" rel="noopener" class="btn-cta-whatsapp">
        @include('partials.whatsapp-icon')
        <span>Consultar</span>
      </a>
    </div>

    {{-- Sin escapar: las descripciones importadas de WooCommerce traen HTML real
         (listas de specs, etc.) — mismo criterio que el bloque html_libre del CMS,
         contenido cargado por el admin, no por un usuario del sitio.
         x-html la mantiene actualizada en vivo tras guardar una edición rápida. --}}
    <div class="product-description" data-reveal x-show="!editing && editDescription" x-html="editDescription">@if($product->description){!! $product->description !!}@endif</div>
    <template x-if="isAdmin">
      <div x-show="editing" x-cloak x-transition>
        <textarea x-model="editDescription" class="admin-edit-input admin-edit-textarea" placeholder="Descripción (se puede usar HTML)"></textarea>
        <div class="admin-edit-actions">
          <button type="button" class="admin-edit-save" :disabled="saving" @click="saveQuickEdit()" x-text="saving ? 'Guardando…' : 'Guardar cambios'"></button>
          <button type="button" class="admin-edit-cancel" :disabled="saving" @click="cancelEdit()">Cancelar</button>
        </div>
      </div>
    </template>

    @if($displaySpecs)
      <table class="spec-table" data-reveal>
        @foreach($displaySpecs as $key => $value)
          <tr><td>{{ $key }}</td><td>{{ $value }}</td></tr>
        @endforeach
      </table>
    @endif
  </div>
</div>

<div class="wrap" data-reveal>
  @include('partials.trust-badges')
</div>

@if($related->count())
<div class="wrap related">
  <h2 data-reveal>También te puede interesar</h2>
  <div class="product-grid" data-reveal-group>
    @foreach($related as $r)
      @include('partials.product-card', ['product' => $r, 'rate' => $rate ?? null, 'forceBob' => $forceBob ?? false])
    @endforeach
  </div>
</div>
@endif

@endsection
