@extends('admin.layout')

@section('title', 'Usuarios')

@section('content')

<div class="admin-table-wrap">
  @if($users->isEmpty())
    <div class="admin-empty">No hay usuarios registrados todavía.</div>
  @else
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Registrado</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $user)
          <tr>
            <td>{{ $user->name }}</td>
            <td style="color:var(--text-secondary);">{{ $user->email }}</td>
            <td class="mono" style="color:var(--text-muted);font-size:12.5px;">{{ $user->created_at->format('d/m/Y') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div style="margin-top:20px;">
  {{ $users->links('partials.pagination') }}
</div>

@endsection
