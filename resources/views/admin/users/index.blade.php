@extends('admin.layout')

@section('title', 'Usuarios')

@section('topbar-actions')
  @if(auth()->user()->isAdmin())
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">+ Nuevo usuario</a>
  @endif
@endsection

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
          <th>Rol</th>
          <th>Registrado</th>
          @if(auth()->user()->isAdmin())<th></th>@endif
        </tr>
      </thead>
      <tbody>
        @foreach($users as $user)
          <tr>
            <td>{{ $user->name }}</td>
            <td style="color:var(--text-secondary);">{{ $user->email }}</td>
            <td>
              <span class="mono" style="color:{{ $user->isAdmin() ? 'var(--gold)' : 'var(--text-secondary)' }};">{{ \App\Models\User::ROLES[$user->role] ?? $user->role }}</span>
            </td>
            <td class="mono" style="color:var(--text-muted);font-size:12.5px;">{{ $user->created_at->format('d/m/Y') }}</td>
            @if(auth()->user()->isAdmin())
              <td>
                <div class="cell-actions">
                  <a href="{{ route('admin.usuarios.edit', $user) }}" class="btn btn-sm">Editar</a>
                  @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.usuarios.destroy', $user) }}" onsubmit="return confirm('¿Eliminar a {{ addslashes($user->name) }}? Esta acción no se puede deshacer.');">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                  @endif
                </div>
              </td>
            @endif
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
