@extends('admin.layout')

@section('title', $menu->exists ? 'Editar menú' : 'Nuevo menú')

@section('content')

<div x-data="{
      items: {{ $menu->relationLoaded('items') ? $menu->items->map(fn($i) => ['id' => $i->id, 'label' => $i->label, 'url' => $i->url, 'page_id' => (string) $i->page_id])->toJson() : '[]' }}
   }">
  <form method="POST" action="{{ $menu->exists ? route('admin.menus.update', $menu) : route('admin.menus.store') }}" class="admin-form">
    @csrf
    @if($menu->exists) @method('PUT') @endif

    <div class="form-section">
      <h3>Menú</h3>

      <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $menu->name) }}" required>
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="key">Key (identifica el menú en el código, ej. header_nav)</label>
        <input type="text" id="key" name="key" value="{{ old('key', $menu->key) }}" required {{ $menu->exists ? 'readonly' : '' }}>
        <div class="form-hint">No se puede cambiar una vez creado, porque el código busca el menú por esta key.</div>
        @error('key') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-section">
      <h3>Ítems</h3>
      <p class="form-hint" style="margin-bottom:14px;">Cada ítem apunta a una página del sitio (selecciónala) o a un link externo/manual (escribe la URL). Si eliges una página, se usa esa por sobre la URL manual.</p>

      <template x-for="(item, i) in items" :key="i">
        <div class="repeater-row" style="grid-template-columns:1fr 1fr 1fr auto;">
          <input type="hidden" :name="'items[' + i + '][id]'" x-model="item.id">
          <input type="text" x-model="item.label" :name="'items[' + i + '][label]'" placeholder="Texto (ej: Inicio)">
          <select x-model="item.page_id" :name="'items[' + i + '][page_id]'">
            <option value="">— Sin página (usar URL) —</option>
            @foreach($pages as $page)
              <option value="{{ $page->id }}">{{ $page->title }}</option>
            @endforeach
          </select>
          <input type="text" x-model="item.url" :name="'items[' + i + '][url]'" placeholder="/ruta o https://...">
          <button type="button" class="repeater-remove" x-on:click="items.splice(i, 1)">×</button>
        </div>
      </template>
      <button type="button" class="btn btn-sm" x-on:click="items.push({id: '', label: '', url: '', page_id: ''})">+ Agregar ítem</button>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.menus.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar menú</button>
    </div>
  </form>
</div>

@endsection
