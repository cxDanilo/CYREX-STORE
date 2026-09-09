@extends('admin.layout')

@section('title', $category->exists ? 'Editar categoría' : 'Nueva categoría')
@section('page-description', 'Nombre, ícono, banner y tipo de atributos de la categoría.')

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
            Déjalo en "Ninguna" para que aparezca como categoría principal en la tienda y el menú flotante.
          @endif
        </div>
        @error('parent_id') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group" x-show="parentId === ''" x-cloak>
        <label for="icon">Ícono predefinido</label>
        <select id="icon" name="icon">
          <option value="">Genérico</option>
          @php
            $iconLabels = [
              'i-cpu' => 'Chip / procesador',
              'i-mouse' => 'Mouse',
              'i-chair' => 'Silla',
              'i-monitor' => 'Monitor',
              'i-plug' => 'Enchufe / accesorio',
              'i-shield-bolt' => 'Escudo con rayo (energía/protección)',
              'i-tools' => 'Herramienta',
              'i-tag' => 'Etiqueta (promoción)',
            ];
          @endphp
          @foreach($icons as $icon)
            <option value="{{ $icon }}" {{ old('icon', $category->icon) === $icon ? 'selected' : '' }}>{{ $iconLabels[$icon] ?? $icon }}</option>
          @endforeach
        </select>
        <div class="form-hint">Se usa solo si no subes una imagen propia abajo.</div>
        @error('icon') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group" x-show="parentId === ''" x-cloak>
        <label>Ícono personalizado</label>
        @include('partials.admin-file-upload', [
          'name' => 'icon_image',
          'accept' => 'image/png,image/jpeg,image/webp,image/svg+xml',
          'currentUrl' => $category->icon_image_url,
          'currentLabel' => 'Ícono actual',
          'hint' => 'Reemplaza el ícono predefinido. PNG/JPG/WEBP/SVG, máx 1MB.',
          'removeName' => $category->icon_image_url ? 'remove_icon_image' : null,
        ])
        @error('icon_image') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label>Banner de la tienda</label>
        @include('partials.admin-file-upload', [
          'name' => 'banner_image',
          'accept' => 'image/png,image/jpeg,image/webp',
          'currentUrl' => $category->banner_image_url,
          'currentLabel' => 'Banner actual',
          'hint' => 'Fondo del encabezado en /tienda para esta categoría. Sin uno propio, se usa uno genérico al azar. Máx 4MB.',
          'removeName' => $category->banner_image_url ? 'remove_banner_image' : null,
        ])
        @error('banner_image') <div class="error">{{ $message }}</div> @enderror
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
          @php $customTypes = \App\Models\AttributeField::whereNotNull('type_label')->pluck('type_label', 'type_key')->unique(); @endphp
          @if($customTypes->isNotEmpty())
            <optgroup label="Atributos personalizados (creados en Admin → Atributos)">
              @foreach($customTypes as $key => $label)
                <option value="{{ $key }}" {{ old('component_type', $category->component_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </optgroup>
          @endif
        </select>
        <div class="form-hint">Marca esto solo en la categoría real donde vas a cargar esos productos. Las piezas de PC hacen que el producto pida sus datos de compatibilidad y aparezca en el asistente "Arma tu PC" — las demás opciones solo agregan un campo extra que se puede usar como filtro en la tienda.</div>
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
