@extends('admin.layout')

@section('title', $category->exists ? 'Editar categoría' : 'Nueva categoría')

@section('content')

<div x-data="{ parentId: @js((string) old('parent_id', $category->parent_id ?? '')) }" style="max-width:560px;">
  <form method="POST" action="{{ $category->exists ? route('admin.categorias.update', $category) : route('admin.categorias.store') }}" class="admin-form" enctype="multipart/form-data">
    @csrf
    @if($category->exists) @method('PUT') @endif

    <div class="form-section">
      <h3>Información general</h3>

      <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required
               x-on:input="if(!$refs.slug.dataset.touched) $refs.slug.value = window.autoSlugify($event.target.value)">
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="slug">Slug (URL)</label>
        <input type="text" id="slug" name="slug" x-ref="slug" value="{{ old('slug', $category->slug) }}" required
               x-on:input="$event.target.dataset.touched = true">
        <div class="form-hint">Se usa para filtrar en la tienda: /tienda?category=<span x-text="$refs.slug ? $refs.slug.value : ''"></span></div>
        @error('slug') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="parent_id">Categoría padre</label>
        <select id="parent_id" name="parent_id" x-model="parentId" {{ $category->exists && $category->children()->exists() ? 'disabled' : '' }}>
          <option value="">Ninguna — es una categoría principal</option>
          @foreach($parents as $parent)
            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
          @endforeach
        </select>
        <div class="form-hint">
          @if($category->exists && $category->children()->exists())
            Esta categoría tiene subcategorías propias, por eso no puede convertirse en hija de otra.
          @else
            Dejalo en "Ninguna" para que aparezca como categoría principal en la tienda y el menú flotante.
          @endif
        </div>
        @error('parent_id') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group" x-show="parentId === ''" x-cloak>
        <label for="icon">Ícono predefinido</label>
        <select id="icon" name="icon">
          <option value="">Genérico</option>
          @foreach($icons as $icon)
            <option value="{{ $icon }}" {{ old('icon', $category->icon) === $icon ? 'selected' : '' }}>{{ $icon }}</option>
          @endforeach
        </select>
        <div class="form-hint">Se usa solo si no subís una imagen propia abajo.</div>
        @error('icon') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group" x-show="parentId === ''" x-cloak>
        <label for="icon_image">Ícono personalizado</label>
        @if($category->icon_image_url)
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
            <img src="{{ $category->icon_image_url }}" alt="" style="width:36px;height:36px;object-fit:contain;background:var(--bg);border-radius:8px;padding:4px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary);">
              <input type="checkbox" name="remove_icon_image" value="1"> Quitar imagen personalizada
            </label>
          </div>
        @endif
        <input type="file" id="icon_image" name="icon_image" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        <div class="form-hint">Reemplaza el ícono predefinido en el menú flotante y el listado. PNG/JPG/WEBP/SVG, máx 1MB.</div>
        @error('icon_image') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="component_type">Tipo de componente / atributos</label>
        <select id="component_type" name="component_type">
          <option value="">No aplica</option>
          <optgroup label="Piezas de PC (aparecen en Arma tu PC)">
            @foreach(config('pc_builder.component_types') as $key => $label)
              <option value="{{ $key }}" {{ old('component_type', $category->component_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </optgroup>
          <optgroup label="Otros (solo atributos/filtros, no aparecen en Arma tu PC)">
            @foreach(config('pc_builder.extra_attribute_types', []) as $key => $label)
              <option value="{{ $key }}" {{ old('component_type', $category->component_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </optgroup>
        </select>
        <div class="form-hint">Marcá esto solo en la categoría real donde vas a cargar esos productos. Las piezas de PC hacen que el producto pida sus datos de compatibilidad y aparezca en el asistente "Arma tu PC" — las demás opciones solo agregan un campo extra que se puede usar como filtro en la tienda.</div>
        @error('component_type') <div class="error">{{ $message }}</div> @enderror
      </div>

      @if($category->exists)
        <div class="form-group">
          <div class="form-hint">El orden entre categorías se maneja arrastrando las filas en el <a href="{{ route('admin.categorias.index') }}">listado de categorías</a>.</div>
        </div>
      @endif
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.categorias.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar categoría</button>
    </div>
  </form>
</div>

@endsection
