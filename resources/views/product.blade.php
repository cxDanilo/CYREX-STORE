@extends('layouts.app')

@section('title', $product->name . ' — Cyrex Store')

@section('content')

@php
  $showBobInitial = $currencyMode === 'bob_only' || ($currencyMode === 'both' && $defaultCurrency === 'BOB');
  $basePriceUsd = $product->currency === 'USD' ? (float) $product->price : (float) $product->price / $rate;
  $priceMainInitial = $showBobInitial
      ? 'Bs '.number_format($basePriceUsd * $rate, 2)
      : '$'.number_format($basePriceUsd, 2);
  $priceAltInitial = $showBobInitial
      ? '≈ $'.number_format($basePriceUsd, 2).' USD'
      : '≈ Bs '.number_format($basePriceUsd * $rate, 2);
  $whatsappNumber = \App\Models\Setting::get('whatsapp_number', '59177947379');
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

<div class="wrap breadcrumb">
  <a href="{{ route('home') }}">Inicio</a> / <a href="{{ route('shop', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a> / {{ $product->name }}
</div>

<div class="wrap product-hero"
     x-data="{
        variant: {{ $product->variants->first()?->id ?? 'null' }},
        showBob: {{ $currencyMode === 'bob_only' || ($currencyMode === 'both' && $defaultCurrency === 'BOB') ? 'true' : 'false' }},
        toggled: false,
        rate: {{ $rate }},
        // basePrice era un valor fijo calculado una sola vez en el
        // servidor — nunca se actualizaba al cambiar de variante. Ahora
        // es un getter: cada variante puede traer su propio
        // price_override (ver Cart.php, que YA lo respeta al agregar al
        // carrito — acá solo faltaba reflejarlo en la pantalla).
        get basePrice() {
          const v = this.variants.find(v => v.id === this.variant);
          const raw = (v && v.price_override !== null && v.price_override !== undefined) ? v.price_override : this.originalPrice;
          return this.editCurrency === 'USD' ? raw : raw / this.rate;
        },
        variants: {{ $product->variants->map(fn($v) => ['id' => $v->id, 'name' => $v->variant_value, 'price_override' => $v->price_override !== null ? (float) $v->price_override : null, 'image' => $v->image_url])->toJson() }},
        inStock: {{ $product->is_sold_out ? 'false' : 'true' }},
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
          this.$watch('variant', () => { this.galleryActive = null; });
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
          if (this.$refs.quickEditImage) this.$refs.quickEditImage.value = '';
          this.editing = false;
        },
        onEditImagePicked(event) {
          const file = event.target.files[0];
          this.editImagePreview = file ? URL.createObjectURL(file) : null;
        },
        async saveQuickEdit() {
          this.saving = true;
          const formData = new FormData();
          formData.append('_method', 'PATCH');
          formData.append('name', this.editName);
          formData.append('price', this.editPrice);
          formData.append('description', this.editDescription ?? '');
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
            if (!res.ok) throw new Error('quick edit failed');
            const data = await res.json();
            this.editName = this.originalName = data.name;
            this.editPrice = this.originalPrice = data.price;
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
    <div class="gallery-thumbs" x-show="galleryImages.length > 1">
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
          <div class="variant-swatch"
               :class="{ selected: variant === {{ $v->id }} }"
               @click="variant = {{ $v->id }}; flashPrice()">
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

    <div class="price-block" x-show="!editing">
      @if($currencyMode === 'both')
        <div class="currency-toggle" style="margin-bottom:14px;">
          <div class="toggle-thumb" :class="toggled ? (showBob ? 'to-bob' : 'to-usd') : (showBob ? 'at-bob' : 'at-usd')"></div>
          <button type="button" @click="toggled = true; showBob = false" :class="!showBob && 'active'">USD</button>
          <button type="button" @click="toggled = true; showBob = true" :class="showBob && 'active'">BOB</button>
        </div>
        @if(\App\Models\Setting::get('show_exchange_rate_badge', 'on') === 'on')
          <div class="exchange-rate-note">1 USD = Bs {{ number_format($rate, 2) }}</div>
        @endif
      @endif
      <div class="price-main"
           x-effect="syncPrice($el, showBob ? basePrice * rate : basePrice, showBob ? 'Bs ' : '$', '')"><span class="price-text">{{ $priceMainInitial }}</span></div>
      @if($currencyMode === 'both')
        <div class="price-alt"
             x-effect="syncPrice($el, showBob ? basePrice : basePrice * rate, showBob ? '≈ $' : '≈ Bs ', showBob ? ' USD' : '')"><span class="price-text">{{ $priceAltInitial }}</span></div>
      @endif
    </div>

    <div class="stock-pill out-of-stock" x-show="!editing && !inStock">✕ Agotado</div>

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
    <div class="product-description" x-show="!editing && editDescription" x-html="editDescription">@if($product->description){!! $product->description !!}@endif</div>
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
      <table class="spec-table">
        @foreach($displaySpecs as $key => $value)
          <tr><td>{{ $key }}</td><td>{{ $value }}</td></tr>
        @endforeach
      </table>
    @endif
  </div>
</div>

@if($related->count())
<div class="wrap related">
  <h2>También te puede interesar</h2>
  <div class="product-grid">
    @foreach($related as $r)
      <a class="card" href="{{ route('product.show', $r->slug) }}" style="display:block;">
        <div class="card-media">
          @if($r->image_thumb_url)
            <img src="{{ $r->image_thumb_url }}" alt="{{ $r->name }}" loading="lazy" style="{{ $r->is_sold_out ? 'filter:grayscale(1);' : '' }}"
                 onload="this.classList.add('is-loaded')" onerror="this.classList.add('is-loaded')">
          @endif
          <div class="card-badges">
            @if($r->is_sold_out)
              <span class="card-badge-agotado">Agotado</span>
            @endif
            @if($promo = $r->activePromotion($cardActivePromotion ?? null))
              <span class="card-badge-promo">{{ $promo->discount_label ?: 'Oferta' }}</span>
            @endif
          </div>
        </div>
        <div class="card-body">
          <div class="card-cat">{{ $r->category->name }}</div>
          <div class="card-name">{{ $r->name }}</div>
          <div class="card-price">
            @if($r->currency === 'USD')
              ${{ number_format($r->price, 2) }}
            @else
              Bs {{ number_format($r->price, 2) }}
            @endif
          </div>
        </div>
      </a>
    @endforeach
  </div>
</div>
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/page-nav.js') }}?v={{ filemtime(public_path('js/page-nav.js')) }}"></script>
@endsection
