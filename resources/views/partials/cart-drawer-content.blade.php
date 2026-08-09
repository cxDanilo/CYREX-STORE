@if($cartItems->isEmpty())
  <p style="color:var(--text-muted);font-size:13.5px;padding:20px 0;">Todavía no agregaste nada al carrito.</p>
@else
  <div class="cart-items">
    @foreach($cartItems as $item)
      <div class="cart-item">
        <div class="cart-item-media">
          @if($item->product->image_url)
            <img src="{{ $item->product->image_url }}" alt="" loading="lazy">
          @endif
        </div>
        <div class="cart-item-body">
          <div class="cart-item-name">
            {{ $item->product->name }}
            @if($item->variant)
              <span style="color:var(--text-muted);">({{ $item->variant->variant_value }})</span>
            @endif
          </div>
          <div class="cart-item-price mono">
            @if($item->currency === 'USD')
              ${{ number_format($item->price, 2) }}
            @else
              Bs {{ number_format($item->price, 2) }}
            @endif
          </div>
        </div>
        <button type="button" class="cart-item-remove" aria-label="Quitar" @click="$store.cart.remove('{{ $item->key }}')">×</button>
      </div>
    @endforeach
  </div>

  <div class="cart-total">
    <span>Total aproximado</span>
    <span class="mono">
      @if($cartCurrency === 'USD')
        ${{ number_format($cartTotal, 2) }}
      @else
        Bs {{ number_format($cartTotal, 2) }}
      @endif
    </span>
  </div>

  <a href="{{ $cartWhatsappUrl }}" target="_blank" class="btn-cta">Finalizar por WhatsApp</a>
@endif
