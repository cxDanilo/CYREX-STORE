@extends('admin.layout')

@section('title', 'Historial de versiones')

@section('content')

<p class="form-hint" style="margin-bottom:20px;">
  Se arma solo en cada actualización del sitio — no hay nada que cargar acá a mano.
  Versión actual: <span class="mono" style="color:var(--gold);">{{ $version ?? '—' }}</span>
</p>

<div class="admin-table-wrap">
  @if(empty($entries))
    <div class="admin-empty">Todavía no hay historial — va a aparecer después de la próxima actualización del sitio.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th></th>
          <th>Qué cambió</th>
        </tr>
      </thead>
      <tbody>
        @foreach($entries as $entry)
          <tr>
            <td class="mono" style="color:var(--text-muted);font-size:12.5px;white-space:nowrap;">
              {{ $entry['date']?->format('d/m/Y H:i') ?? '—' }}
            </td>
            <td class="mono" style="color:var(--text-muted);font-size:11.5px;">{{ $entry['hash'] }}</td>
            <td>{{ $entry['subject'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

@endsection
