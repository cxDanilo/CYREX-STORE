@extends('admin.layout')

@section('title', 'Ajustes')
@section('page-description', 'Configura la apariencia y el comportamiento de la tienda.')

@section('content')

{{-- Ningún campo de acá lleva el atributo required en su <input>, aun
     cuando el servidor sí los exige (SettingsController@update) — con
     pestañas, un required que queda oculto (display:none en otra
     pestaña que la activa) hace que el navegador bloquee el envío del
     formulario en silencio, sin mandar el POST ni mostrar ningún
     error, apenas se intenta guardar desde una pestaña distinta a la
     suya. La validación real la sigue haciendo el servidor. --}}
@php
  $tabErrorFields = [
    'general' => ['rate', 'currency_mode', 'default_currency', 'show_exchange_rate_badge', 'category_menu_scope', 'auto_promo_category_id'],
    'apariencia' => ['logo', 'logo_height', 'accent_color', 'reduced_motion', 'new_banner_images.*', 'pcbuilder_hero', 'quote_banner_text', 'quote_banner_color'],
    'tienda' => ['shop_cta_text', 'whatsapp_btn_text', 'footer_whatsapp_btn_text', 'footer_tagline'],
    'integraciones' => ['ga4_measurement_id', 'whatsapp_number', 'whatsapp_community_url', 'whatsapp_community_btn_text'],
  ];
  $errorTab = null;
  foreach ($tabErrorFields as $tabKey => $fields) {
      if ($errors->hasAny($fields)) { $errorTab = $tabKey; break; }
  }
@endphp
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
      x-data="{
        tab: {!! $errorTab ? "'{$errorTab}'" : "localStorage.getItem('cyrexAdminSettingsTab') || 'general'" !!},
        dirty: false,
        saving: false,
      }"
      x-init="
        $watch('tab', v => localStorage.setItem('cyrexAdminSettingsTab', v));
        window.addEventListener('beforeunload', (e) => { if (dirty && !saving) { e.preventDefault(); e.returnValue = ''; } });
      "
      x-on:input="dirty = true" x-on:change="dirty = true" x-on:submit="saving = true">
  @csrf
  @method('PUT')

  <div class="admin-tabs" style="margin-bottom:22px;">
    <button type="button" class="admin-tab" :class="{ active: tab === 'general' }" @click="tab = 'general'">General</button>
    <button type="button" class="admin-tab" :class="{ active: tab === 'apariencia' }" @click="tab = 'apariencia'">Apariencia</button>
    <button type="button" class="admin-tab" :class="{ active: tab === 'tienda' }" @click="tab = 'tienda'">Tienda</button>
    <button type="button" class="admin-tab" :class="{ active: tab === 'integraciones' }" @click="tab = 'integraciones'">Integraciones</button>
  </div>

  {{-- ===================== GENERAL ===================== --}}
  <div class="admin-form" x-show="tab === 'general'" x-cloak>
    <div class="form-section">
      <h3>Tasa de cambio</h3>
      <p class="form-hint" style="margin-bottom:14px;">Tasa actual: <span class="mono" style="color:var(--gold);">{{ number_format($currentRate, 2) }}</span> BOB por USD.</p>

      <div class="form-group">
        <label for="rate">Nueva tasa (déjalo vacío para no cambiarla)</label>
        <input type="number" step="0.01" min="0.01" id="rate" name="rate" placeholder="{{ number_format($currentRate, 2) }}">
        @error('rate') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Visualización de precios</h3>

      <div class="form-group">
        <label for="currency_mode">Qué moneda mostrar en el sitio</label>
        <div class="segmented">
          @foreach(['both' => 'Ambas', 'usd_only' => 'Solo USD', 'bob_only' => 'Solo BOB'] as $value => $optLabel)
            <label class="segmented-option">
              <input type="radio" name="currency_mode" value="{{ $value }}" {{ old('currency_mode', $currencyMode) === $value ? 'checked' : '' }}>
              <span>{{ $optLabel }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <div class="form-group">
        <label for="default_currency">Moneda principal por defecto</label>
        <div class="segmented">
          @foreach(['USD' => 'USD', 'BOB' => 'BOB'] as $value => $optLabel)
            <label class="segmented-option">
              <input type="radio" name="default_currency" value="{{ $value }}" {{ old('default_currency', $defaultCurrency) === $value ? 'checked' : '' }}>
              <span>{{ $optLabel }}</span>
            </label>
          @endforeach
        </div>
        <div class="form-hint">Se usa cuando el modo de arriba es "Ambas" — define qué precio se ve primero en la página de producto.</div>
      </div>

      <div class="form-group">
        <input type="hidden" name="show_exchange_rate_badge" value="off">
        <label class="switch">
          <input type="checkbox" name="show_exchange_rate_badge" value="on" {{ old('show_exchange_rate_badge', $showExchangeRateBadge) === 'on' ? 'checked' : '' }}>
          <span class="switch-track"></span>
          <span class="switch-label">Mostrar la tasa del día en la página de producto</span>
        </label>
        <div class="form-hint">Un texto chico ("1 USD = Bs {{ number_format($currentRate, 2) }}") junto al selector USD/BOB, solo aplica cuando arriba está en "Ambas".</div>
        @error('show_exchange_rate_badge') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Navegación</h3>

      <div class="form-group">
        <label for="category_menu_scope">Menú flotante de categorías</label>
        <div class="segmented">
          @foreach(['shop' => 'Solo en Tienda', 'all' => 'Todo el sitio'] as $value => $optLabel)
            <label class="segmented-option">
              <input type="radio" name="category_menu_scope" value="{{ $value }}" {{ old('category_menu_scope', $categoryMenuScope) === $value ? 'checked' : '' }}>
              <span>{{ $optLabel }}</span>
            </label>
          @endforeach
        </div>
        <div class="form-hint">En mobile las categorías siempre están disponibles desde el menú hamburguesa, sin importar esta opción.</div>
      </div>

      <div class="form-group">
        <label for="auto_promo_category_id">Categoría automática de ofertas</label>
        <select id="auto_promo_category_id" name="auto_promo_category_id">
          <option value="">Desactivado</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ (string) old('auto_promo_category_id', $autoPromoCategoryId) === (string) $cat->id ? 'selected' : '' }}>
              {{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}
            </option>
          @endforeach
        </select>
        <div class="form-hint">Mientras haya una elegida acá: cualquier producto con oferta activa ahora mismo (Admin → Descuentos) aparece listado en esa categoría solo, sin que nadie lo agregue ni lo saque a mano — se actualiza solo cuando la oferta empieza y cuando termina.</div>
        @error('auto_promo_category_id') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>

  {{-- ===================== APARIENCIA ===================== --}}
  <div class="admin-form" x-show="tab === 'apariencia'" x-cloak>
    <div class="form-section">
      <h3>Marca</h3>

      <div class="form-group">
        <label>Logo</label>
        @include('partials.admin-file-upload', [
          'name' => 'logo',
          'currentUrl' => $logoPath ? asset('uploads/'.$logoPath) : asset('images/logo-horizontal.png'),
          'currentLabel' => $logoPath ? 'Logo actual' : 'Logo por defecto',
          'hint' => 'PNG con fondo transparente recomendado. Máx. 2 MB.',
          'removeName' => $logoPath ? 'remove_logo' : null,
        ])
        @error('logo') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="logo_height">Alto del logo (px)</label>
        <input type="number" min="20" max="120" id="logo_height" name="logo_height" value="{{ old('logo_height', $logoHeight) }}">
        <div class="form-hint">Se aplica al logo del header, footer y menú mobile. Entre 20 y 120px.</div>
        @error('logo_height') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Diseño</h3>

      <div class="form-group">
        <label for="accent_color">Color de acento</label>
        <div style="display:flex;gap:10px;align-items:center;">
          <input type="color" id="accent_color_picker" value="{{ old('accent_color', $accentColor) }}" style="width:44px;height:38px;padding:2px;background:var(--bg-elevated-2);border:1px solid var(--border);border-radius:8px;cursor:pointer;" onchange="document.getElementById('accent_color').value = this.value;">
          <input type="text" id="accent_color" name="accent_color" value="{{ old('accent_color', $accentColor) }}" style="flex:1;" oninput="document.getElementById('accent_color_picker').value = this.value;">
        </div>
        <div class="form-hint">Reemplaza el dorado (#FFD900) en todo el sitio. Usalo con cuidado — es la identidad visual de la marca.</div>
        @error('accent_color') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <input type="hidden" name="reduced_motion" value="off">
        <label class="switch">
          <input type="checkbox" name="reduced_motion" value="on" {{ old('reduced_motion', $reducedMotion) === 'on' ? 'checked' : '' }}>
          <span class="switch-track"></span>
          <span class="switch-label">Reducir animaciones</span>
        </label>
        <div class="form-hint">Apaga las animaciones decorativas (brillos, marquesinas, pulsos) en todo el sitio.</div>
      </div>
    </div>

    <div class="form-section">
      <h3>Banner de Tienda</h3>
      <p class="form-hint" style="margin-bottom:14px;">De fondo, detrás del título "Tienda" — se elige una al azar en cada visita. Sube varias para que varíe. Recomendado: horizontal, 1600x500px aprox.</p>

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
        <div class="form-hint">Puedes seleccionar varias a la vez.</div>
        @error('new_banner_images.*') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Banner de Arma tu PC</h3>
      <p class="form-hint" style="margin-bottom:14px;">De fondo, detrás del título "Arma tu equipo pieza por pieza". Recomendado: horizontal, 1600x500px aprox.</p>

      <div class="form-group">
        @include('partials.admin-file-upload', [
          'name' => 'pcbuilder_hero',
          'currentUrl' => $pcbuilderHeroImage ? asset('uploads/'.$pcbuilderHeroImage) : null,
          'currentLabel' => 'Imagen actual',
          'hint' => 'PNG, JPG o WEBP.',
          'removeName' => $pcbuilderHeroImage ? 'remove_pcbuilder_hero' : null,
        ])
        @error('pcbuilder_hero') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Banner de cotización PDF</h3>
      <p class="form-hint" style="margin-bottom:14px;">Texto y color del banner en el PDF de "Arma tu PC" — cambialo por temporada (Navidad, Año Nuevo, Día de la madre, etc.).</p>

      <div class="form-group">
        <label for="quote_banner_text">Texto del banner</label>
        <input type="text" id="quote_banner_text" name="quote_banner_text" value="{{ old('quote_banner_text', $quoteBannerText) }}" maxlength="60">
        @error('quote_banner_text') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="quote_banner_color">Color del banner</label>
        <div style="display:flex;gap:10px;align-items:center;">
          <input type="color" id="quote_banner_color_picker" value="{{ old('quote_banner_color', $quoteBannerColor) }}" style="width:44px;height:38px;padding:2px;background:var(--bg-elevated-2);border:1px solid var(--border);border-radius:8px;cursor:pointer;" onchange="document.getElementById('quote_banner_color').value = this.value;">
          <input type="text" id="quote_banner_color" name="quote_banner_color" value="{{ old('quote_banner_color', $quoteBannerColor) }}" style="flex:1;" oninput="document.getElementById('quote_banner_color_picker').value = this.value;">
        </div>
        @error('quote_banner_color') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>

  {{-- ===================== TIENDA ===================== --}}
  <div class="admin-form" x-show="tab === 'tienda'" x-cloak>
    <div class="form-section">
      <h3>Header y footer</h3>

      <div class="form-row">
        <div class="form-group">
          <label for="shop_cta_text">Texto del botón "Ver tienda" (header)</label>
          <input type="text" id="shop_cta_text" name="shop_cta_text" value="{{ old('shop_cta_text', $shopCtaText) }}">
          @error('shop_cta_text') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
          <label for="whatsapp_btn_text">Texto del botón de WhatsApp (header)</label>
          <input type="text" id="whatsapp_btn_text" name="whatsapp_btn_text" value="{{ old('whatsapp_btn_text', $whatsappBtnText) }}">
          @error('whatsapp_btn_text') <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="form-group">
        <label for="footer_whatsapp_btn_text">Texto del botón de WhatsApp (footer)</label>
        <input type="text" id="footer_whatsapp_btn_text" name="footer_whatsapp_btn_text" value="{{ old('footer_whatsapp_btn_text', $footerWhatsappBtnText) }}">
        @error('footer_whatsapp_btn_text') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="footer_tagline">Frase del footer</label>
        <input type="text" id="footer_tagline" name="footer_tagline" value="{{ old('footer_tagline', $footerTagline) }}">
        @error('footer_tagline') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>

  {{-- ===================== INTEGRACIONES ===================== --}}
  <div class="admin-form" x-show="tab === 'integraciones'" x-cloak>
    <div class="form-section">
      <h3>Analítica</h3>

      <div class="form-group">
        <label for="ga4_measurement_id">ID de medición de Google Analytics 4</label>
        <input type="text" id="ga4_measurement_id" name="ga4_measurement_id" value="{{ old('ga4_measurement_id', $ga4MeasurementId) }}" placeholder="G-XXXXXXXXXX">
        <div class="form-hint">Lo sacas de tu cuenta de Google Analytics (analytics.google.com) → Administrar → Flujos de datos → tu sitio. Déjalo vacío para no cargar Analytics en el sitio.</div>
        @error('ga4_measurement_id') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>WhatsApp</h3>

      <div class="form-group">
        <label for="whatsapp_number">Número de WhatsApp (con código de país, sin +, sin espacios)</label>
        <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $whatsappNumber) }}" placeholder="59177947379">
        <div class="form-hint">A este número llega el mensaje de "Finalizar por WhatsApp" del carrito.</div>
        @error('whatsapp_number') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="whatsapp_community_url">Link de la comunidad de WhatsApp</label>
        <input type="url" id="whatsapp_community_url" name="whatsapp_community_url" value="{{ old('whatsapp_community_url', $whatsappCommunityUrl) }}" placeholder="https://chat.whatsapp.com/...">
        <div class="form-hint">Agrega un botón en el footer del sitio. Déjalo vacío para no mostrar el botón.</div>
        @error('whatsapp_community_url') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="whatsapp_community_btn_text">Texto del botón de la comunidad</label>
        <input type="text" id="whatsapp_community_btn_text" name="whatsapp_community_btn_text" value="{{ old('whatsapp_community_btn_text', $whatsappCommunityBtnText) }}" maxlength="60">
        @error('whatsapp_community_btn_text') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>

  <div class="form-actions">
    <span class="form-actions-status" x-show="!dirty && !saving">Sin cambios sin guardar</span>
    <button type="submit" class="btn btn-primary" :disabled="(!dirty || saving)">
      <span class="btn-spinner" x-show="saving" x-cloak></span>
      <span x-text="saving ? 'Guardando…' : 'Guardar ajustes'"></span>
    </button>
  </div>
</form>

@if(auth()->user()->isAdmin())
  {{-- Fuera del <form> de ajustes a propósito: son acciones puntuales
       (disparan algo ahora mismo), no campos que se guardan con el
       resto — iban antes en el pie del sidebar, mezcladas con la
       navegación, y no era su lugar. --}}
  <div class="admin-form" style="margin-top:20px;">
    <div class="form-section">
      <h3>Mantenimiento</h3>
      <p class="form-hint" style="margin-bottom:16px;">Acciones sobre el sitio en vivo — no dependen de "Guardar ajustes" de arriba.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <form method="POST" action="{{ route('admin.cache.purge') }}">
          @csrf
          <button type="submit" class="btn">@include('partials.admin-icon', ['name' => 'purgar']) Purgar caché</button>
        </form>
        <form method="POST" action="{{ route('admin.backup.trigger') }}" onsubmit="return confirm('¿Disparar un backup ahora? Tarda uno o dos minutos.');">
          @csrf
          <button type="submit" class="btn">@include('partials.admin-icon', ['name' => 'backup']) Backup ahora</button>
        </form>
      </div>
    </div>
  </div>
@endif

@endsection
