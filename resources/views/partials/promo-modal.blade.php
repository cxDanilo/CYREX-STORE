@if($promoModal)
  <div class="promo-modal-overlay"
       x-data="{
          open: false,
          storageKey: @js('promo_modal_seen_'.$promoModal->slug),
          init() {
            if (localStorage.getItem(this.storageKey) === '1') return;
            setTimeout(() => {
              this.open = true;
              this.$store.promoModal.open = true;
              localStorage.setItem(this.storageKey, '1');
            }, 2500);
          },
          close() {
            this.open = false;
            this.$store.promoModal.open = false;
          }
       }"
       x-show="open"
       x-transition:enter="promo-modal-overlay-enter" x-transition:enter-start="promo-modal-overlay-enter-start" x-transition:enter-end="promo-modal-overlay-enter-end"
       x-transition:leave="promo-modal-overlay-leave" x-transition:leave-start="promo-modal-overlay-leave-start" x-transition:leave-end="promo-modal-overlay-leave-end"
       @click="close()"
       x-cloak>
    <div class="promo-modal" @click.stop
         x-transition:enter="promo-modal-enter" x-transition:enter-start="promo-modal-enter-start" x-transition:enter-end="promo-modal-enter-end">
      <button type="button" class="promo-modal-close" @click="close()" aria-label="Cerrar">×</button>
      <div class="promo-modal-eyebrow">{{ $promoModal->discount_label ?: 'Ofertas por tiempo limitado' }}</div>
      <h2 class="promo-modal-title">{{ $promoModal->banner_text }}</h2>
      <a href="{{ route('shop') }}" class="btn btn-primary promo-modal-cta" @click="close()">Ver ofertas</a>
    </div>
  </div>
@endif
