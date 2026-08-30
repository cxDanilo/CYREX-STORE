@extends('admin.layout')

@section('title', $user->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('content')

<div style="max-width:520px;">
  <form method="POST" action="{{ $user->exists ? route('admin.usuarios.update', $user) : route('admin.usuarios.store') }}" class="admin-form">
    @csrf
    @if($user->exists) @method('PUT') @endif

    <div class="form-section">
      <h3>Datos de acceso</h3>

      <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" {{ $user->exists ? '' : 'required' }}>
        <div class="form-hint">{{ $user->exists ? 'Déjalo vacío para no cambiarla.' : 'Mínimo 8 caracteres.' }}</div>
        @error('password') <div class="error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label for="role">Rol</label>
        <select id="role" name="role" required>
          @foreach(\App\Models\User::ROLES as $value => $label)
            <option value="{{ $value }}" {{ old('role', $user->role ?? 'editor') === $value ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <div class="form-hint">Administrador: acceso total, incluida la gestión de usuarios. Editor: todo el resto del panel, sin poder crear/editar/eliminar usuarios.</div>
        @error('role') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.usuarios.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar usuario</button>
    </div>
  </form>
</div>

@endsection
