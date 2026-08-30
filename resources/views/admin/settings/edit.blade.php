@extends('admin.layout')

@section('title', 'Ajustes')

@section('content')

<form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form" enctype="multipart/form-data">
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

    <div class="form-group">
      <label for="show_exchange_rate_badge">Mostrar la tasa del día en la página de producto</label>
      <select id="show_exchange_rate_badge" name="show_exchange_rate_badge" required>
        <option value="on" {{ old('show_exchange_rate_badge', $showExchangeRateBadge) === 'on' ? 'selected' : '' }}>Sí</option>
        <option value="off" {{ old('show_exchange_rate_badge', $showExchangeRateBadge) === 'off' ? 'selected' : '' }}>No</option>
      </select>
      <div class="form-hint">Un texto chico ("1 USD = Bs {{ number_format($currentRate, 2) }}") junto al selector USD/BOB, solo aplica cuando arriba está en "Ambas".</div>
      @error('show_exchange_rate_badge') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-section">
    <h3>Marca</h3>

    <div class="form-group">
      <label for="logo">Logo</label>
      <div style="display:flex;gap:16px;align-items:center;">
        <div style="width:64px;height:64px;border-radius:12px;background:var(--bg-elevated-2);border:1px solid var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center;overflow:hidden;">
          <img src="{{ $logoPath ? asset('uploads/'.$logoPath) : asset('images/logo-horizontal.png') }}" alt="" style="max-width:100%;max-height:100%;">
        </div>
        <div style="flex:1;">
          <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
          <div class="form-hint">PNG con fondo transparente recomendado. Máx. 2 MB.</div>
          @if($logoPath)
            <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:13px;color:var(--text-secondary);">
              <input type="checkbox" name="remove_logo" value="1">
              Volver al logo por defecto
            </label>
          @endif
        </div>
      </div>
      @error('logo') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="logo_height">Alto del logo (px)</label>
      <input type="number" min="20" max="120" id="logo_height" name="logo_height" value="{{ old('logo_height', $logoHeight) }}" required>
      <div class="form-hint">Se aplica al logo del header, footer y menú mobile. Entre 20 y 120px.</div>
      @error('logo_height') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-section">
    <h3>Header y footer</h3>

    <div class="form-row">
      <div class="form-group">
        <label for="shop_cta_text">Texto del botón "Ver tienda" (header)</label>
        <input type="text" id="shop_cta_text" name="shop_cta_text" value="{{ old('shop_cta_text', $shopCtaText) }}" required>
        @error('shop_cta_text') <div class="error">{{ $message }}</div> @enderror
      </div>
      <div class="form-group">
        <label for="whatsapp_btn_text">Texto del botón de WhatsApp (header)</label>
        <input type="text" id="whatsapp_btn_text" name="whatsapp_btn_text" value="{{ old('whatsapp_btn_text', $whatsappBtnText) }}" required>
        @error('whatsapp_btn_text') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-group">
      <label for="footer_whatsapp_btn_text">Texto del botón de WhatsApp (footer)</label>
      <input type="text" id="footer_whatsapp_btn_text" name="footer_whatsapp_btn_text" value="{{ old('footer_whatsapp_btn_text', $footerWhatsappBtnText) }}" required>
      @error('footer_whatsapp_btn_text') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="footer_tagline">Frase del footer</label>
      <input type="text" id="footer_tagline" name="footer_tagline" value="{{ old('footer_tagline', $footerTagline) }}" required>
      @error('footer_tagline') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-section">
    <h3>Diseño</h3>

    <div class="form-group">
      <label for="accent_color">Color de acento</label>
      <div style="display:flex;gap:10px;align-items:center;">
        <input type="color" id="accent_color_picker" value="{{ old('accent_color', $accentColor) }}" style="width:44px;height:38px;padding:2px;background:var(--bg-elevated-2);border:1px solid var(--border);border-radius:8px;cursor:pointer;" onchange="document.getElementById('accent_color').value = this.value;">
        <input type="text" id="accent_color" name="accent_color" value="{{ old('accent_color', $accentColor) }}" style="flex:1;" oninput="document.getElementById('accent_color_picker').value = this.value;" required>
      </div>
      <div class="form-hint">Reemplaza el dorado (#FFD900) en todo el sitio. Usalo con cuidado — es la identidad visual de la marca.</div>
      @error('accent_color') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="reduced_motion">Animaciones</label>
      <select id="reduced_motion" name="reduced_motion" required>
        <option value="off" {{ old('reduced_motion', $reducedMotion) === 'off' ? 'selected' : '' }}>Activadas (normal)</option>
        <option value="on" {{ old('reduced_motion', $reducedMotion) === 'on' ? 'selected' : '' }}>Reducidas</option>
      </select>
      <div class="form-hint">"Reducidas" apaga las animaciones decorativas (brillos, marquesinas, pulsos) en todo el sitio.</div>
    </div>
  </div>

  <div class="form-section">
    <h3>Banner de cotización PDF</h3>
    <p class="form-hint" style="margin-bottom:14px;">Texto y color del banner en el PDF de "Arma tu PC" — cambialo por temporada (Navidad, Año Nuevo, Día de la madre, etc.).</p>

    <div class="form-group">
      <label for="quote_banner_text">Texto del banner</label>
      <input type="text" id="quote_banner_text" name="quote_banner_text" value="{{ old('quote_banner_text', $quoteBannerText) }}" maxlength="60" required>
      @error('quote_banner_text') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="quote_banner_color">Color del banner</label>
      <div style="display:flex;gap:10px;align-items:center;">
        <input type="color" id="quote_banner_color_picker" value="{{ old('quote_banner_color', $quoteBannerColor) }}" style="width:44px;height:38px;padding:2px;background:var(--bg-elevated-2);border:1px solid var(--border);border-radius:8px;cursor:pointer;" onchange="document.getElementById('quote_banner_color').value = this.value;">
        <input type="text" id="quote_banner_color" name="quote_banner_color" value="{{ old('quote_banner_color', $quoteBannerColor) }}" style="flex:1;" oninput="document.getElementById('quote_banner_color_picker').value = this.value;" required>
      </div>
      @error('quote_banner_color') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-section">
    <h3>Banner de Tienda</h3>
    <p class="form-hint" style="margin-bottom:14px;">De fondo, detrás del título "Tienda" — se elige una al azar en cada visita. Subí varias para que varíe. Recomendado: horizontal, 1600x500px aprox.</p>

    @if(count($shopBannerImages))
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:16px;">
        @foreach($shopBannerImages as $img)
          <div>
            <img src="{{ asset('uploads/'.$img) }}" style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
            <label style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;color:var(--text-secondary);">
              <input type="checkbox" name="remove_banner_images[]" value="{{ $img }}">
              Quitar
            </label>
          </div>
        @endforeach
      </div>
    @endif

    <div class="form-group">
      <label for="new_banner_images">Agregar imágenes</label>
      <input type="file" id="new_banner_images" name="new_banner_images[]" accept="image/png,image/jpeg,image/webp" multiple>
      <div class="form-hint">Podés seleccionar varias a la vez.</div>
      @error('new_banner_images.*') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-section">
    <h3>Banner de Arma tu PC</h3>
    <p class="form-hint" style="margin-bottom:14px;">De fondo, detrás del título "Armá tu equipo pieza por pieza". Recomendado: horizontal, 1600x500px aprox.</p>

    <div class="form-group">
      @if($pcbuilderHeroImage)
        <img src="{{ asset('uploads/'.$pcbuilderHeroImage) }}" style="width:100%;max-width:320px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;border:1px solid var(--border);margin-bottom:10px;">
      @endif
      <label for="pcbuilder_hero">{{ $pcbuilderHeroImage ? 'Reemplazar imagen' : 'Subir imagen' }}</label>
      <input type="file" id="pcbuilder_hero" name="pcbuilder_hero" accept="image/png,image/jpeg,image/webp">
      @if($pcbuilderHeroImage)
        <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:13px;color:var(--text-secondary);">
          <input type="checkbox" name="remove_pcbuilder_hero" value="1">
          Quitar imagen
        </label>
      @endif
      @error('pcbuilder_hero') <div class="error">{{ $message }}</div> @enderror
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
    <h3>Analítica</h3>

    <div class="form-group">
      <label for="ga4_measurement_id">ID de medición de Google Analytics 4</label>
      <input type="text" id="ga4_measurement_id" name="ga4_measurement_id" value="{{ old('ga4_measurement_id', $ga4MeasurementId) }}" placeholder="G-XXXXXXXXXX">
      <div class="form-hint">Lo sacás de tu cuenta de Google Analytics (analytics.google.com) → Administrar → Flujos de datos → tu sitio. Dejalo vacío para no cargar Analytics en el sitio.</div>
      @error('ga4_measurement_id') <div class="error">{{ $message }}</div> @enderror
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

    <div class="form-group">
      <label for="whatsapp_community_url">Link de la comunidad de WhatsApp</label>
      <input type="url" id="whatsapp_community_url" name="whatsapp_community_url" value="{{ old('whatsapp_community_url', $whatsappCommunityUrl) }}" placeholder="https://chat.whatsapp.com/...">
      <div class="form-hint">Agrega un botón en el footer del sitio. Dejalo vacío para no mostrar el botón.</div>
      @error('whatsapp_community_url') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="whatsapp_community_btn_text">Texto del botón de la comunidad</label>
      <input type="text" id="whatsapp_community_btn_text" name="whatsapp_community_btn_text" value="{{ old('whatsapp_community_btn_text', $whatsappCommunityBtnText) }}" maxlength="60" required>
      @error('whatsapp_community_btn_text') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Guardar ajustes</button>
  </div>
</form>

@endsection
