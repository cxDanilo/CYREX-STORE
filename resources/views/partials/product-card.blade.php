@php
  // Card de producto compartida por tienda, relacionados y los 3 bloques
  // CMS que listan productos (productos/categoria-rotativa/banner-productos)
  // — antes vivía copiada a mano en esos 5 archivos (2 de ellos, byte por
  // byte idénticos), lo que hacía que un cambio en uno no se reflejara en
  // los demás. $cardActivePromotion ya llega compartido por el View::composer
  // de AppServiceProvider para todas esas vistas, así que no hace falta
  // pasarlo — @include hereda el scope completo de quien lo llama.
  $quickAdd = $quickAdd ?? false;
  $forceBob = $forceBob ?? false;
@endphp
<a class="card" href="{{ route('product.show', $product->slug) }}" style="display:block;"
   @if($quickAdd)
   x-data="{
     showVariants: false,
     hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
     variants: {{ $product->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->variant_value, 'is_sold_out' => $v->is_sold_out])->toJson() }},
     inStock: {{ $product->is_sold_out ? 'false' : 'true' }},
     quickAdd() {
       if (!this.inStock) return;
       if (this.hasVariants) { this.showVariants = !this.showVariants; }
       else { $store.cart.add({{ $product->id }}, null); }
     }
   }"
   @click.outside="showVariants = false"
   @endif>
  <div class="card-media">
    @if($product->image_thumb_url)
      <img src="{{ $product->image_thumb_url }}" alt="{{ $product->name }}" loading="lazy" style="{{ $product->is_sold_out ? 'filter:grayscale(1);' : '' }}"
           onload="markCardImageLoaded(this)" onerror="markCardImageLoaded(this)">
    @endif
    <div class="card-badges">
      @if($product->is_sold_out)
        <span class="card-badge-agotado">Agotado</span>
      @endif
      @if($promo = $product->activePromotion($cardActivePromotion ?? null))
        <span class="card-badge-promo">{{ $promo->discount_label ?: 'Oferta' }}</span>
      @endif
      @if($product->hasActiveOffer())
        <span class="card-badge-promo">-{{ $product->offerDiscountPercent() }}%</span>
      @endif
    </div>
    @if($quickAdd)
      <button type="button" class="card-quick-add" :disabled="!inStock" @click.stop.prevent="quickAdd()" aria-label="Agregar {{ $product->name }} al carrito">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.4 12.4a2 2 0 0 0 2 1.6h7.2a2 2 0 0 0 2-1.6L20 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.3" fill="currentColor"/><circle cx="17" cy="20" r="1.3" fill="currentColor"/></svg>
      </button>

      @if($product->has_variants)
        <div class="card-variant-picker" x-show="showVariants" x-cloak x-transition.opacity.duration.150ms @click.stop.prevent="true">
          <div class="card-variant-picker-title">Elige una opción</div>
          <div class="card-variant-picker-options">
            <template x-for="v in variants" :key="v.id">
              <button type="button" class="card-variant-chip" :class="{ 'out-of-stock': v.is_sold_out }" :disabled="v.is_sold_out"
                      @click.stop.prevent="$store.cart.add({{ $product->id }}, v.id); showVariants = false"
                      x-text="v.is_sold_out ? v.name + ' (Agotado)' : v.name"></button>
            </template>
          </div>
        </div>
      @endif
    @endif
  </div>
  <div class="card-body">
    <div class="card-cat">{{ $product->category->name }}</div>
    <div class="card-name">{{ $product->name }}</div>
    @if($product->hasActiveOffer())
      <div class="card-price-original">
        @if($forceBob)
          Bs {{ number_format($product->priceInBob($rate), 2) }}
        @elseif($product->currency === 'USD')
          ${{ number_format($product->price, 2) }}
        @else
          Bs {{ number_format($product->price, 2) }}
        @endif
      </div>
    @endif
    <div class="card-price">
      @if($product->hasVariantPriceRange())<span class="card-price-from">Desde</span>@endif
      @if($forceBob)
        Bs {{ number_format($product->displayPriceInBob($rate), 2) }} <small>BOB</small>
      @elseif($product->currency === 'USD')
        ${{ number_format($product->displayPrice(), 2) }} <small>USD</small>
      @else
        Bs {{ number_format($product->displayPrice(), 2) }} <small>BOB</small>
      @endif
    </div>
    @if($product->hasActiveOffer())
      <div class="card-offer-countdown" x-text="$store.offer.remaining"></div>
    @endif
  </div>
</a>
