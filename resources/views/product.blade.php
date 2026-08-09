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
        basePrice: {{ $product->currency === 'USD' ? $product->price : $product->price / $rate }},
        variants: {{ $product->variants->map(fn($v) => ['id' => $v->id, 'name' => $v->variant_value, 'inStock' => $v->stock > 0])->toJson() }},
        productInStock: {{ $product->stock > 0 ? 'true' : 'false' }},
        get inStock() {
          if (!this.variants.length) return this.productInStock;
          const v = this.variants.find(v => v.id === this.variant);
          return v ? v.inStock : false;
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
        editName: {{ Js::from($product->name) }},
        editPrice: {{ (float) $product->price }},
        editCurrency: {{ Js::from($product->currency) }},
        editDescription: {{ Js::from($product->description ?? '') }},
        editImageUrl: {{ Js::from($product->image_url) }},
        editImagePreview: null,
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
            this.basePrice = this.editCurrency === 'USD' ? data.price : data.price / this.rate;
            if (this.$refs.quickEditImage) this.$refs.quickEditImage.value = '';
            this.editing = false;
          } catch (e) {
            alert('No se pudo guardar el cambio. Probá de nuevo.');
          } finally {
            this.saving = false;
          }
        }
     }">
  <div class="gallery">
    <div class="gallery-main">
      <img src="{{ $product->image_url }}" :src="editImagePreview || editImageUrl" x-show="editImagePreview || editImageUrl" alt="{{ $product->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:20px;">
      <template x-if="isAdmin && editing">
        <label class="admin-edit-image-overlay">
          <span>Cambiar imagen</span>
          <input type="file" x-ref="quickEditImage" accept="image/png,image/jpeg,image/webp" @change="onEditImagePicked($event)" style="display:none;">
        </label>
      </template>
    </div>
  </div>

  <div class="product-info">
    <template x-if="isAdmin">
      <button type="button" class="admin-edit-toggle" :class="editing && 'is-active'" @click="toggleEdit()" x-text="editing ? 'Cancelar edición' : '✏️ Editar producto'"></button>
    </template>

    <div class="cat-eyebrow">{{ $product->category->name }}</div>
    <h1 x-show="!editing" x-cloak x-text="editName">{{ $product->name }}</h1>
    <template x-if="isAdmin">
      <input type="text" x-show="editing" x-cloak x-model="editName" class="admin-edit-input admin-edit-input-name" x-transition>
    </template>

    @if($product->has_variants && $product->variants->count())
      <div class="variant-options">
        @foreach($product->variants as $v)
          <div class="variant-swatch"
               :class="{ selected: variant === {{ $v->id }}, 'out-of-stock': !{{ $v->stock > 0 ? 'true' : 'false' }} }"
               @click="{{ $v->stock > 0 ? 'true' : 'false' }} && (variant = {{ $v->id }})">
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

    <div class="price-block" x-show="!editing" x-cloak>
      @if($currencyMode === 'both')
        <div class="currency-toggle" style="margin-bottom:14px;">
          <div class="toggle-thumb" :class="toggled ? (showBob ? 'to-bob' : 'to-usd') : (showBob ? 'at-bob' : 'at-usd')"></div>
          <button type="button" @click="toggled = true; showBob = false" :class="!showBob && 'active'">USD</button>
          <button type="button" @click="toggled = true; showBob = true" :class="showBob && 'active'">BOB</button>
        </div>
      @endif
      <div class="price-main" x-text="showBob ? 'Bs ' + (basePrice * rate).toFixed(2) : '$' + basePrice.toFixed(2)"
           x-effect="showBob; if (toggled) { $el.classList.remove('price-flash'); void $el.offsetWidth; $el.classList.add('price-flash'); }">{{ $priceMainInitial }}</div>
      @if($currencyMode === 'both')
        <div class="price-alt" x-text="showBob ? '≈ $' + basePrice.toFixed(2) + ' USD' : '≈ Bs ' + (basePrice * rate).toFixed(2)"
             x-effect="showBob; if (toggled) { $el.classList.remove('price-flash'); void $el.offsetWidth; $el.classList.add('price-flash'); }">{{ $priceAltInitial }}</div>
      @endif
    </div>

    <div class="stock-pill" x-show="!editing" x-cloak :class="inStock ? 'in-stock' : 'out-of-stock'" x-text="inStock ? '✓ Disponible' : '✕ Agotado'"></div>

    <div class="btn-cta-row">
      <button type="button" class="btn-cta"
              :class="$store.cart.has({{ $product->id }}, variant) && 'in-cart'"
              :disabled="!inStock"
              @click="$store.cart.has({{ $product->id }}, variant) ? $store.cart.remove({{ $product->id }} + ':' + (variant ?? '')) : $store.cart.add({{ $product->id }}, variant)">
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
    <div class="product-description" x-show="!editing && editDescription" x-cloak x-html="editDescription">@if($product->description){!! $product->description !!}@endif</div>
    <template x-if="isAdmin">
      <div x-show="editing" x-cloak x-transition>
        <textarea x-model="editDescription" class="admin-edit-input admin-edit-textarea" placeholder="Descripción (se puede usar HTML)"></textarea>
        <div class="admin-edit-actions">
          <button type="button" class="admin-edit-save" :disabled="saving" @click="saveQuickEdit()" x-text="saving ? 'Guardando…' : 'Guardar cambios'"></button>
          <button type="button" class="admin-edit-cancel" :disabled="saving" @click="cancelEdit()">Cancelar</button>
        </div>
      </div>
    </template>

    @if($product->specs)
      <table class="spec-table">
        @foreach($product->specs as $key => $value)
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
      @php
        $rOutOfStock = $r->has_variants
            ? $r->variants->every(fn ($v) => $v->stock <= 0)
            : $r->stock <= 0;
      @endphp
      <a class="card" href="{{ route('product.show', $r->slug) }}" style="display:block;">
        <div class="card-media">
          @if($r->image_url)
            <img src="{{ $r->image_url }}" alt="{{ $r->name }}" loading="lazy">
          @endif
          @if($rOutOfStock)
            <span class="card-badge-agotado">Agotado</span>
          @endif
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
