@if($showExchangeRateFloat)
  <div class="exchange-rate-float">
    <button type="button" class="exchange-rate-float-close" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <div class="exchange-rate-float-label">
      <svg viewBox="0 0 24 24" fill="none"><path d="M3 12a9 9 0 0 1 15.4-6.4L21 8M21 3v5h-5M21 12a9 9 0 0 1-15.4 6.4L3 16m0 5v-5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Tasa del día
    </div>
    <div class="exchange-rate-float-value">1 USD = Bs {{ number_format($exchangeRate, 2) }}</div>
  </div>
@endif
