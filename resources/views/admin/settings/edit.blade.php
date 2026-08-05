@extends('admin.layout')

@section('title', 'Ajustes')

@section('content')

<form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form">
  @csrf
  @method('PUT')

  <div class="form-section">
    <h3>Tasa de cambio</h3>
    <p class="form-hint" style="margin-bottom:14px;">Tasa actual: <span class="mono" style="color:var(--gold);">{{ number_format($currentRate, 2) }}</span> BOB por USD.</p>

    <div class="form-group">
      <label for="rate">Nueva tasa (dejalo vacío para no cambiarla)</label>
      <input type="number" step="0.01" min="0.01" id="rate" name="rate" placeholder="{{ number_format($currentRate, 2) }}">
      @error('rate') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-section">
    <h3>Visualización de precios</h3>

    <div class="form-group">
      <label for="currency_mode">Qué moneda mostrar en el sitio</label>
      <select id="currency_mode" name="currency_mode" required>
        <option value="both" {{ old('currency_mode', $currencyMode) === 'both' ? 'selected' : '' }}>Ambas (con selector USD/BOB)</option>
        <option value="usd_only" {{ old('currency_mode', $currencyMode) === 'usd_only' ? 'selected' : '' }}>Solo USD</option>
        <option value="bob_only" {{ old('currency_mode', $currencyMode) === 'bob_only' ? 'selected' : '' }}>Solo BOB</option>
      </select>
    </div>

    <div class="form-group">
      <label for="default_currency">Moneda principal por defecto</label>
      <select id="default_currency" name="default_currency" required>
        <option value="USD" {{ old('default_currency', $defaultCurrency) === 'USD' ? 'selected' : '' }}>USD</option>
        <option value="BOB" {{ old('default_currency', $defaultCurrency) === 'BOB' ? 'selected' : '' }}>BOB</option>
      </select>
      <div class="form-hint">Se usa cuando el modo de arriba es "Ambas" — define qué precio se ve primero en la página de producto.</div>
    </div>
  </div>

  <div class="form-section">
    <h3>Navegación</h3>

    <div class="form-group">
      <label for="category_menu_scope">Menú flotante de categorías</label>
      <select id="category_menu_scope" name="category_menu_scope" required>
        <option value="shop" {{ old('category_menu_scope', $categoryMenuScope) === 'shop' ? 'selected' : '' }}>Solo en la página Tienda</option>
        <option value="all" {{ old('category_menu_scope', $categoryMenuScope) === 'all' ? 'selected' : '' }}>En todo el sitio</option>
      </select>
      <div class="form-hint">En mobile las categorías siempre están disponibles desde el menú hamburguesa, sin importar esta opción.</div>
    </div>
  </div>

  <div class="form-section">
    <h3>WhatsApp</h3>

    <div class="form-group">
      <label for="whatsapp_number">Número de WhatsApp (con código de país, sin +, sin espacios)</label>
      <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $whatsappNumber) }}" placeholder="59177947379" required>
      <div class="form-hint">A este número llega el mensaje de "Finalizar por WhatsApp" del carrito.</div>
      @error('whatsapp_number') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Guardar ajustes</button>
  </div>
</form>

@endsection
