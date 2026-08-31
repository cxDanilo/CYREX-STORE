@extends('admin.layout')

@section('title', 'Plantillas')

@section('topbar-actions')
  <a href="{{ route('admin.plantillas.create') }}" class="btn btn-primary">+ Nueva plantilla</a>
@endsection

@section('content')

<p class="form-hint" style="margin-bottom:20px;">Al crear una página nueva con una plantilla, se generan automáticamente sus bloques iniciales (vacíos, con los valores por defecto de cada tipo) — se editan después desde el editor visual.</p>

<div class="admin-table-wrap">
  @if($templates->isEmpty())
    <div class="admin-empty">Todavía no hay plantillas.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Estructura</th>
          <th>Páginas</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($templates as $template)
          <tr>
            <td class="admin-table-title">
              {{ $template->name }}
              @if($template->description)
                <div style="color:var(--text-muted);font-size:12px;margin-top:2px;font-weight:400;">{{ $template->description }}</div>
              @endif
            </td>
            <td style="color:var(--text-secondary);font-size:13px;" data-label="Estructura" class="admin-table-block">
              {{ collect($template->default_blocks)->implode(' → ') ?: '— sin bloques —' }}
            </td>
            <td class="mono" data-label="Páginas">{{ $template->pages_count }}</td>
            <td class="cell-actions">
              <div class="cell-actions">
                <a href="{{ route('admin.plantillas.edit', $template) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.plantillas.destroy', $template) }}" onsubmit="return confirm('¿Eliminar la plantilla {{ $template->name }}? Las páginas que la usan no se borran, solo quedan sin plantilla.');">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

@endsection
