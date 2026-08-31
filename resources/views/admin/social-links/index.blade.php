@extends('admin.layout')

@section('title', 'Redes sociales')

@section('topbar-actions')
  <a href="{{ route('admin.redes.create') }}" class="btn btn-primary">+ Nueva red</a>
@endsection

@section('content')

<div class="admin-table-wrap">
  @if($socialLinks->isEmpty())
    <div class="admin-empty">Todavía no hay redes sociales cargadas.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th></th>
          <th>Plataforma</th>
          <th>URL</th>
          <th>Orden</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($socialLinks as $link)
          <tr>
            <td style="width:28px;color:var(--gold);">@include('partials.social-icon', ['platform' => $link->platform])</td>
            <td style="text-transform:capitalize;" class="admin-table-title">{{ $link->platform }}</td>
            <td class="mono" style="color:var(--text-secondary);" data-label="URL">{{ $link->url }}</td>
            <td class="mono" data-label="Orden">{{ $link->sort_order }}</td>
            <td class="cell-actions">
              <div class="cell-actions">
                <a href="{{ route('admin.redes.edit', $link) }}" class="btn btn-sm">Editar</a>
                <form method="POST" action="{{ route('admin.redes.destroy', $link) }}" onsubmit="return confirm('¿Eliminar este link?');">
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
