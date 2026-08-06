@extends('admin.layout')

@section('title', $template->exists ? 'Editar plantilla' : 'Nueva plantilla')

@section('content')

<div x-data="{
      blocks: {{ json_encode(old('default_blocks', $template->default_blocks ?? [])) }},
      moveUp(i) { if (i === 0) return; [this.blocks[i-1], this.blocks[i]] = [this.blocks[i], this.blocks[i-1]]; },
      moveDown(i) { if (i === this.blocks.length - 1) return; [this.blocks[i+1], this.blocks[i]] = [this.blocks[i], this.blocks[i+1]]; }
   }" style="max-width:640px;">
  <form method="POST" action="{{ $template->exists ? route('admin.plantillas.update', $template) : route('admin.plantillas.store') }}" class="admin-form">
    @csrf
    @if($template->exists) @method('PUT') @endif

    <div class="form-section">
      <h3>Plantilla</h3>

      <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $template->name) }}" required
               x-on:input="if(!$refs.slug.dataset.touched) $refs.slug.value = window.autoSlugify($event.target.value)">
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="slug">Slug</label>
        <input type="text" id="slug" name="slug" x-ref="slug" value="{{ old('slug', $template->slug) }}" required
               x-on:input="$event.target.dataset.touched = true">
        @error('slug') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="description">Descripción (opcional, solo para referencia interna)</label>
        <input type="text" id="description" name="description" value="{{ old('description', $template->description) }}">
        @error('description') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Estructura inicial de bloques</h3>
      <p class="form-hint" style="margin-bottom:14px;">El orden acá define el orden en que aparecen los bloques al crear una página nueva con esta plantilla.</p>

      <template x-for="(block, i) in blocks" :key="i">
        <div class="repeater-row" style="grid-template-columns:1fr auto auto auto;">
          <select x-model="blocks[i]" :name="'default_blocks[' + i + ']'">
            @foreach($blockTypes as $bt)
              <option value="{{ $bt['type'] }}">{{ $bt['label'] }}</option>
            @endforeach
          </select>
          <button type="button" class="repeater-remove" x-on:click="moveUp(i)" title="Subir">↑</button>
          <button type="button" class="repeater-remove" x-on:click="moveDown(i)" title="Bajar">↓</button>
          <button type="button" class="repeater-remove" x-on:click="blocks.splice(i, 1)" title="Quitar">×</button>
        </div>
      </template>
      <button type="button" class="btn btn-sm" x-on:click="blocks.push('{{ $blockTypes[0]['type'] ?? '' }}')">+ Agregar bloque</button>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.plantillas.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar plantilla</button>
    </div>
  </form>
</div>

@endsection
