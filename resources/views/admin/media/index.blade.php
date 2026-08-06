@extends('admin.layout')

@section('title', 'Medios')

@section('content')

<div class="media-layout">
  <aside class="media-sidebar">
    <form method="GET" class="admin-search" style="margin-bottom:20px;">
      @if(request('folder'))<input type="hidden" name="folder" value="{{ request('folder') }}">@endif
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre...">
      <button type="submit" class="btn btn-sm">Buscar</button>
    </form>

    <div class="media-sidebar-section">
      <h4>Carpetas</h4>
      <a href="{{ route('admin.medios.index') }}" class="media-folder-link {{ !request('folder') ? 'active' : '' }}">Todos</a>
      @foreach($folders as $folder)
        <div class="media-folder-row">
          <a href="{{ route('admin.medios.index', ['folder' => $folder->id]) }}" class="media-folder-link {{ (string) request('folder') === (string) $folder->id ? 'active' : '' }}">
            {{ $folder->name }} <span class="mono">({{ $folder->media_count }})</span>
          </a>
          <form method="POST" action="{{ route('admin.medios.carpetas.destroy', $folder) }}" onsubmit="return confirm('¿Eliminar la carpeta {{ $folder->name }}? Los archivos no se borran, solo quedan sin carpeta.');">
            @csrf @method('DELETE')
            <button type="submit" class="media-folder-delete" aria-label="Eliminar carpeta">×</button>
          </form>
        </div>
      @endforeach

      <form method="POST" action="{{ route('admin.medios.carpetas.store') }}" style="margin-top:10px;display:flex;gap:6px;">
        @csrf
        <input type="text" name="name" placeholder="Nueva carpeta" required style="flex:1;">
        <button type="submit" class="btn btn-sm">+</button>
      </form>
    </div>

    @if($tags->isNotEmpty())
      <div class="media-sidebar-section">
        <h4>Etiquetas</h4>
        <div class="media-tag-cloud">
          @foreach($tags as $tag)
            <a href="{{ route('admin.medios.index', ['tag' => $tag->slug]) }}" class="media-tag-chip {{ request('tag') === $tag->slug ? 'is-active' : '' }}">{{ $tag->name }}</a>
          @endforeach
        </div>
      </div>
    @endif
  </aside>

  <div class="media-main"
       x-data="{
          dragging: false,
          uploading: false,
          folderId: '{{ request('folder') }}',
          upload(fileList) {
            if (!fileList || !fileList.length) return;
            const formData = new FormData();
            Array.from(fileList).forEach(f => formData.append('files[]', f));
            if (this.folderId) formData.append('folder_id', this.folderId);
            this.uploading = true;
            fetch('{{ route('admin.medios.store') }}', {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
              body: formData,
            }).then(r => r.json()).then(() => { window.location.reload(); })
              .catch(() => { this.uploading = false; alert('Error al subir.'); });
          }
       }">
    <div class="media-dropzone"
         :class="dragging && 'is-dragging'"
         x-on:dragover.prevent="dragging = true"
         x-on:dragleave.prevent="dragging = false"
         x-on:drop.prevent="dragging = false; upload($event.dataTransfer.files);">
      <p x-show="!uploading">Arrastrá imágenes acá, o</p>
      <p x-show="uploading" x-cloak>Subiendo…</p>
      <label class="btn btn-sm" x-show="!uploading">
        Elegir archivos
        <input type="file" multiple accept="image/*" x-on:change="upload($event.target.files)" style="display:none;">
      </label>
      <div class="form-group" style="max-width:240px;margin:14px auto 0;">
        <select x-model="folderId">
          <option value="">Sin carpeta</option>
          @foreach($folders as $folder)
            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="media-grid">
      @forelse($media as $item)
        <div class="media-card" x-data="{ editing: false }">
          <div class="media-card-thumb">
            <img src="{{ $item->thumb_url }}" alt="{{ $item->alt_text }}" loading="lazy">
            @if($item->webp_url)<span class="media-card-badge">WebP</span>@endif
          </div>
          <div class="media-card-body">
            <div class="media-card-name" title="{{ $item->original_name }}">{{ $item->original_name }}</div>
            @if($item->tags->isNotEmpty())
              <div class="media-card-tags">
                @foreach($item->tags as $tag)
                  <span class="media-tag-chip">{{ $tag->name }}</span>
                @endforeach
              </div>
            @endif
            <div class="media-card-actions">
              <button type="button" class="btn btn-sm" x-on:click="navigator.clipboard.writeText('{{ $item->url }}')">Copiar URL</button>
              <button type="button" class="btn btn-sm" x-on:click="editing = !editing">Editar</button>
              <form method="POST" action="{{ route('admin.medios.destroy', $item) }}" onsubmit="return confirm('¿Eliminar {{ addslashes($item->original_name) }}?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
              </form>
            </div>

            <form method="POST" action="{{ route('admin.medios.update', $item) }}" enctype="multipart/form-data" x-show="editing" x-transition class="media-card-edit" x-cloak>
              @csrf @method('PUT')
              <input type="text" name="alt_text" value="{{ $item->alt_text }}" placeholder="Texto alternativo" class="gjs-field">
              <input type="text" name="tags" value="{{ $item->tags->pluck('name')->implode(', ') }}" placeholder="etiquetas, separadas, por coma" class="gjs-field">
              <select name="folder_id" class="gjs-field">
                <option value="">Sin carpeta</option>
                @foreach($folders as $folder)
                  <option value="{{ $folder->id }}" {{ (int) $item->folder_id === $folder->id ? 'selected' : '' }}>{{ $folder->name }}</option>
                @endforeach
              </select>
              <label class="form-hint" style="display:block;margin-bottom:8px;">
                Reemplazar archivo (mantiene la misma URL):
                <input type="file" name="file" accept="image/*">
              </label>
              <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
            </form>
          </div>
        </div>
      @empty
        <div class="admin-empty">Todavía no subiste ningún archivo.</div>
      @endforelse
    </div>

    <div style="margin-top:20px;">
      {{ $media->links('partials.pagination') }}
    </div>
  </div>
</div>

@endsection
