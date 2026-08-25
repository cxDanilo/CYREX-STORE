@extends('admin.layout')

@section('title', $promotion->exists ? 'Editar promoción' : 'Nueva promoción')

@section('content')

<div x-data="{ isRecurring: {{ old('is_recurring', $promotion->is_recurring) ? 'true' : 'false' }} }" style="max-width:640px;">
  <form method="POST" action="{{ $promotion->exists ? route('admin.promociones.update', $promotion) : route('admin.promociones.store') }}" class="admin-form">
    @csrf
    @if($promotion->exists) @method('PUT') @endif

    <div class="form-section">
      <h3>Información general</h3>

      <div class="form-group">
        <label for="name">Nombre interno</label>
        <input type="text" id="name" name="name" value="{{ old('name', $promotion->name) }}" required
               x-on:input="if(!$refs.slug.dataset.touched) $refs.slug.value = window.autoSlugify($event.target.value)">
        <div class="form-hint">Solo para identificarla acá en el admin — ej. "Día de la Madre 2027".</div>
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="slug">Slug</label>
        <input type="text" id="slug" name="slug" x-ref="slug" value="{{ old('slug', $promotion->slug) }}" required
               x-on:input="$event.target.dataset.touched = true">
        @error('slug') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="category_id">Categoría (opcional)</label>
        <select id="category_id" name="category_id">
          <option value="">Ninguna — solo productos marcados manualmente</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ (string) old('category_id', $promotion->category_id) === (string) $cat->id ? 'selected' : '' }}>
              {{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}
            </option>
          @endforeach
        </select>
        <div class="form-hint">Si la elegís, todos los productos de esa categoría entran en la promo automáticamente.</div>
      </div>

      <label style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="active" value="1" {{ old('active', $promotion->active) ? 'checked' : '' }}>
        Activa
      </label>
      <div class="form-hint">Apagala para dejarla lista sin que se muestre todavía en el sitio.</div>
    </div>

    <div class="form-section">
      <h3>Textos</h3>

      <div class="form-group">
        <label for="banner_text">Texto de la barra (cuando está activa)</label>
        <input type="text" id="banner_text" name="banner_text" value="{{ old('banner_text', $promotion->banner_text) }}" required>
        <div class="form-hint">Ej. "Ofertas Día de la Madre — hasta 30% OFF".</div>
        @error('banner_text') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="teaser_text">Texto de expectativa (antes de arrancar)</label>
        <input type="text" id="teaser_text" name="teaser_text" value="{{ old('teaser_text', $promotion->teaser_text) }}">
        <div class="form-hint">Dejalo vacío para no mostrar aviso previo — la barra recién aparece cuando arranca la promo.</div>
        @error('teaser_text') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="discount_label">Etiqueta de descuento (badge en productos)</label>
        <input type="text" id="discount_label" name="discount_label" value="{{ old('discount_label', $promotion->discount_label) }}" placeholder="Hasta 30% OFF">
        <div class="form-hint">Solo texto — no calcula precios, es lo que se ve en el badge de la card de producto.</div>
        @error('discount_label') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Fechas</h3>

      <div class="form-group">
        <label for="teaser_starts_at">Arranca la expectativa</label>
        <input type="date" id="teaser_starts_at" name="teaser_starts_at" value="{{ old('teaser_starts_at', optional($promotion->teaser_starts_at)->format('Y-m-d')) }}">
        @error('teaser_starts_at') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="starts_at">Arranca la promo</label>
          <input type="date" id="starts_at" name="starts_at" value="{{ old('starts_at', optional($promotion->starts_at)->format('Y-m-d')) }}" required>
          @error('starts_at') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
          <label for="ends_at">Termina la promo</label>
          <input type="date" id="ends_at" name="ends_at" value="{{ old('ends_at', optional($promotion->ends_at)->format('Y-m-d')) }}" required>
          @error('ends_at') <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>

      <label style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="is_recurring" value="1" x-model="isRecurring">
        Se repite todos los años (Navidad, Día de la Madre, etc.)
      </label>
      <div class="form-hint">Si la marcás, las fechas de arriba solo definen cuántos días dura — el día del evento de abajo se reproyecta cada año solo.</div>

      <div x-show="isRecurring" x-cloak class="form-row" style="margin-top:14px;">
        <div class="form-group">
          <label for="recurring_day">Día del evento</label>
          <input type="number" id="recurring_day" name="recurring_day" min="1" max="31" value="{{ old('recurring_day', $promotion->recurring_day) }}">
          @error('recurring_day') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
          <label for="recurring_month">Mes del evento</label>
          <input type="number" id="recurring_month" name="recurring_month" min="1" max="12" value="{{ old('recurring_month', $promotion->recurring_month) }}">
          <div class="form-hint">Ej. Navidad = día 25, mes 12.</div>
          @error('recurring_month') <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Modal (nivel 3)</h3>
      <label style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="show_as_modal" value="1" {{ old('show_as_modal', $promotion->show_as_modal) ? 'checked' : '' }}>
        Mostrar también como modal en la home
      </label>
      <div class="form-hint">Reservado para 1-2 fechas grandes al año. Aparece una sola vez por sesión, nunca junto con la barra de anuncio.</div>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.promociones.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
  </form>
</div>

@endsection
