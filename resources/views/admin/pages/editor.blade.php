@extends('admin.layout')

@section('title', 'Editar contenido — ' . $page->title)

@section('content')

<div class="cms-editor-topbar">
  <div class="cms-editor-devices">
    <button type="button" class="btn btn-sm" data-cms-device="Desktop">Escritorio</button>
    <button type="button" class="btn btn-sm" data-cms-device="Tablet">Tablet</button>
    <button type="button" class="btn btn-sm" data-cms-device="Mobile">Celular</button>
  </div>
  <div class="cms-editor-actions">
    <button type="button" id="cms-undo-btn" class="btn btn-sm">← Deshacer</button>
    <button type="button" id="cms-redo-btn" class="btn btn-sm">Rehacer →</button>
    <span id="cms-save-status" class="cms-save-status">—</span>
    <button type="button" id="cms-save-btn" class="btn btn-sm btn-primary">Guardar ahora</button>
  </div>
</div>

<div class="cms-editor-layout">
  <div id="gjs" class="cms-editor-canvas"></div>

  <aside class="cms-editor-sidebar">
    <div class="cms-editor-panel">
      <h4>Bloques</h4>
      <div id="gjs-blocks-list"></div>
    </div>
    <div class="cms-editor-panel">
      <h4>Orden de bloques</h4>
      <div id="gjs-layers-list"></div>
    </div>
    <div class="cms-editor-panel">
      <h4>Contenido del bloque seleccionado</h4>
      <div id="gjs-settings-panel"></div>
    </div>
  </aside>
</div>

@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.21.13/dist/css/grapes.min.css">
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.13/dist/grapes.min.js"></script>
<script src="{{ asset('js/cms/grapesjs-editor.js') }}?v={{ filemtime(public_path('js/cms/grapesjs-editor.js')) }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    CyrexCmsEditor.init({
      mountId: 'gjs',
      indexUrl: '{{ route('admin.paginas.bloques.index', $page) }}',
      storeUrl: '{{ route('admin.paginas.bloques.store', $page) }}',
      previewUrl: '{{ route('admin.paginas.bloques.preview', $page) }}',
      csrfToken: '{{ csrf_token() }}',
      cssUrl: '{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}',
      fontsUrl: 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap',
    });
  });
</script>
@endsection
