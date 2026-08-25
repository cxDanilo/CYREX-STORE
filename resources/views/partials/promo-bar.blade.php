@if($promoBarActive || $promoBarTeaser)
  <div class="promo-bar {{ $promoBarActive ? 'promo-bar-active' : 'promo-bar-teaser' }}"
       x-data="{
          dismissed: true,
          remaining: '',
          endsAt: @js($promoBarActive?->currentEndsAt()?->toIso8601String()),
          storageKey: @js('promo_dismissed_'.($promoBarActive ? 'active_'.$promoBarActive->slug : 'teaser_'.$promoBarTeaser->slug)),
          init() {
            this.dismissed = localStorage.getItem(this.storageKey) === '1';
            if (this.endsAt) {
              this.tick();
              setInterval(() => this.tick(), 1000);
            }
          },
          tick() {
            const diff = Math.max(0, new Date(this.endsAt) - new Date());
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            this.remaining = (d > 0 ? d + 'd ' : '') + String(h).padStart(2, '0') + 'h ' + String(m).padStart(2, '0') + 'm ' + String(s).padStart(2, '0') + 's';
          },
          dismiss() {
            this.dismissed = true;
            localStorage.setItem(this.storageKey, '1');
          }
       }"
       x-show="!dismissed && !($store.promoModal && $store.promoModal.open)"
       x-transition:enter="promo-bar-enter" x-transition:enter-start="promo-bar-enter-start" x-transition:enter-end="promo-bar-enter-end"
       x-cloak>
    <div class="wrap promo-bar-inner">
      <span class="promo-bar-text">
        @if($promoBarActive)
          {{ $promoBarActive->banner_text }}
          <span class="promo-bar-countdown" x-text="remaining"></span>
        @else
          {{ $promoBarTeaser->teaser_text }}
        @endif
      </span>
      <button type="button" class="promo-bar-close" @click="dismiss()" aria-label="Cerrar aviso">×</button>
    </div>
  </div>
@endif
