{{--
  Modal genérico reusable — pensado para cuando hace falta mostrar
  contenido real (no un simple sí/no, para eso ya alcanza con
  confirm()). Uso: envolver este include en un x-data que tenga la
  variable de apertura, ej.:

    <div x-data="{ showX: false }">
      <button type="button" @click="showX = true">Abrir</button>
      @include('partials.admin-modal', ['open' => 'showX', 'title' => 'Título'])
    </div>

  El contenido va donde dice "CONTENIDO ACÁ" — como esto es un
  @include (no un componente Blade con slot), lo más simple es copiar
  este archivo dentro de la vista que lo necesite y completar el
  cuerpo, en vez de forzar un slot vía variables.
--}}
<div class="modal-overlay" x-show="{{ $open }}" x-cloak x-transition.opacity.duration.150ms
     x-on:keydown.escape.window="{{ $open }} = false" x-on:click.self="{{ $open }} = false">
  <div class="modal" x-show="{{ $open }}" x-transition.scale.duration.150ms>
    <div class="modal-title">{{ $title ?? '' }}</div>
    <div class="modal-body">
      {{-- CONTENIDO ACÁ --}}
    </div>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" @click="{{ $open }} = false">Cancelar</button>
    </div>
  </div>
</div>
