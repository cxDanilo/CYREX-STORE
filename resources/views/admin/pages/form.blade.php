@extends('admin.layout')

@section('title', $page->exists ? 'Editar página' : 'Nueva página')

@section('content')

<div x-data="{}" style="max-width:640px;">
  <form method="POST" action="{{ $page->exists ? route('admin.paginas.update', $page) : route('admin.paginas.store') }}" class="admin-form">
    @csrf
    @if($page->exists) @method('PUT') @endif

    <div class="form-section">
      <h3>Página</h3>

      <div class="form-group">
        <label for="title">Título</label>
        <input type="text" id="title" name="title" value="{{ old('title', $page->title) }}" required
               x-on:input="if(!$refs.slug.dataset.touched) $refs.slug.value = window.autoSlugify($event.target.value)">
        @error('title') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="slug">Slug (URL: /slug)</label>
        <input type="text" id="slug" name="slug" x-ref="slug" value="{{ old('slug', $page->slug) }}" required
               x-on:input="$event.target.dataset.touched = true">
        @error('slug') <div class="error">{{ $message }}</div> @enderror
      </div>

      @if(! $page->exists)
        <div class="form-group">
          <label for="template_id">Plantilla</label>
          <select id="template_id" name="template_id">
            <option value="">— Sin plantilla (página vacía) —</option>
            @foreach($templates as $template)
              <option value="{{ $template->id }}">{{ $template->name }} ({{ count($template->default_blocks ?? []) }} bloques)</option>
            @endforeach
          </select>
          <div class="form-hint">Crea automáticamente los bloques iniciales de la plantilla elegida. Después de crear la página, se puede editar cada bloque desde "Contenido".</div>
          @error('template_id') <div class="error">{{ $message }}</div> @enderror
        </div>
      @else
        <div class="form-group">
          <label>Plantilla</label>
          <div class="form-hint" style="margin-top:0;">{{ $page->template?->name ?? 'Sin plantilla' }} — no se puede cambiar acá porque no vuelve a generar bloques (evita pisar contenido ya cargado). El contenido se edita desde "Contenido" en el listado.</div>
        </div>
      @endif

      <div class="form-group">
        <label for="status">Estado</label>
        <select id="status" name="status" required>
          <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>Borrador (no visible al público)</option>
          <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>Publicada</option>
        </select>
      </div>
    </div>

    <div class="form-section">
      <h3>SEO</h3>

      <div class="form-group">
        <label for="meta_title">Meta título (opcional, si se deja vacío usa el título)</label>
        <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}">
      </div>

      <div class="form-group">
        <label for="meta_description">Meta descripción (opcional)</label>
        <textarea id="meta_description" name="meta_description" rows="2">{{ old('meta_description', $page->meta_description) }}</textarea>
      </div>
    </div>

    <div class="form-section">
      <h3>Navegación</h3>

      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;">
          <input type="checkbox" name="show_in_footer" value="1" {{ old('show_in_footer', $page->show_in_footer) ? 'checked' : '' }}>
          Mostrar en el footer
        </label>
      </div>

      <div class="form-group">
        <label for="footer_sort_order">Orden en el footer</label>
        <input type="number" min="0" id="footer_sort_order" name="footer_sort_order" value="{{ old('footer_sort_order', $page->footer_sort_order ?? 0) }}">
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.paginas.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">{{ $page->exists ? 'Guardar cambios' : 'Crear página' }}</button>
    </div>
  </form>
</div>

@endsection
