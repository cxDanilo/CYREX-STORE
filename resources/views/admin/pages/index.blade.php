@extends('admin.layout')

@section('title', 'Páginas')

@section('topbar-actions')
  <a href="{{ route('admin.paginas.create') }}" class="btn btn-primary">+ Nueva página</a>
@endsection

@section('content')

<div class="admin-table-wrap">
  @if($pages->isEmpty())
    <div class="admin-empty">Todavía no hay páginas.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Título</th>
          <th>Slug</th>
          <th>Plantilla</th>
          <th>Estado</th>
          <th>Footer</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($pages as $page)
          <tr>
            <td>{{ $page->title }}</td>
            <td class="mono" style="color:var(--text-secondary);">/{{ $page->slug }}</td>
            <td style="color:var(--text-secondary);">{{ $page->template?->name ?? '—' }}</td>
            <td><span class="status-badge {{ $page->status === 'published' ? 'active' : 'inactive' }}">{{ $page->status === 'published' ? 'Publicada' : 'Borrador' }}</span></td>
            <td class="mono">{{ $page->show_in_footer ? 'Sí' : 'No' }}</td>
            <td>
              <div class="cell-actions">
                <a href="{{ route('admin.paginas.content', $page) }}" class="btn btn-sm">Contenido</a>
                <a href="{{ route('admin.paginas.edit', $page) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.paginas.destroy', $page) }}" onsubmit="return confirm('¿Eliminar {{ addslashes($page->title) }} y todo su contenido?');">
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

<div style="margin-top:20px;">
  {{ $pages->links('partials.pagination') }}
</div>

@endsection
