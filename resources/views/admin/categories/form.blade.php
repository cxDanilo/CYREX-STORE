@extends('admin.layout')

@section('title', $category->exists ? 'Editar categoría' : 'Nueva categoría')

@section('content')

<div x-data="{ parentId: @js((string) old('parent_id', $category->parent_id ?? '')) }" style="max-width:560px;">
  <form method="POST" action="{{ $category->exists ? route('admin.categorias.update', $category) : route('admin.categorias.store') }}" class="admin-form">
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
        <label for="icon">Ícono</label>
        <select id="icon" name="icon">
          <option value="">Genérico</option>
          @foreach($icons as $icon)
            <option value="{{ $icon }}" {{ old('icon', $category->icon) === $icon ? 'selected' : '' }}>{{ $icon }}</option>
          @endforeach
        </select>
        <div class="form-hint">Solo se usa en categorías principales — aparece en el menú flotante de la tienda.</div>
        @error('icon') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="sort_order">Orden</label>
        <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" required>
        <div class="form-hint">Controla el orden en el menú de categorías — menor número aparece primero.</div>
        @error('sort_order') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.categorias.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar categoría</button>
    </div>
  </form>
</div>

@endsection
