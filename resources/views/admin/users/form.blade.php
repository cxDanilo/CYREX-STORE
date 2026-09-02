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

    <div class="form-section">
      <h3>Referidos</h3>
      <div class="form-hint" style="margin-bottom:14px;">Si le cargás un WhatsApp propio, cualquier visitante que entre por su link ve los botones de WhatsApp del sitio apuntando a este número, en vez del general de Ajustes — así no se te escapa un cliente al que ya atendiste.</div>

      @if($user->exists && $user->ref_code)
        <div class="form-group">
          <label>Su link para compartir</label>
          <div style="display:flex;gap:8px;">
            <input type="text" id="ref-link" readonly value="{{ route('home') }}?ref={{ $user->ref_code }}" class="mono" onclick="this.select()">
            <button type="button" class="btn btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('ref-link').value).then(() => { this.textContent = '¡Copiado!'; setTimeout(() => this.textContent = 'Copiar', 1500); })">Copiar</button>
          </div>
          <div class="form-hint">El código (<span class="mono">{{ $user->ref_code }}</span>) se generó solo al crear el usuario — no se puede editar, para que este link nunca deje de funcionar.</div>
        </div>
      @else
        <div class="form-hint">El link para compartir se genera solo al guardar por primera vez.</div>
      @endif

      <div class="form-group">
        <label for="whatsapp_number">WhatsApp personal</label>
        <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" placeholder="59177947379">
        <div class="form-hint">Con código de país, solo números. Si lo dejás vacío, su link no hace nada (cae al número general).</div>
        @error('whatsapp_number') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.usuarios.index') }}" class="btn">Cancelar</a>
      <button type="submit" class="btn btn-primary">Guardar usuario</button>
    </div>
  </form>
</div>

@endsection
