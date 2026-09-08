<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión — Cyrex Store Admin</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v={{ filemtime(public_path('css/fonts.css')) }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>

<div class="admin-login-wrap">
  <div class="admin-login-card">
    <img src="{{ $logoUrl }}" alt="Cyrex Store" class="admin-login-logo-img">
    <div class="admin-login-sub">Acceso administradores</div>

    @if($lockoutSeconds)
      <div class="form-group"><div class="error" id="lockout-msg">Demasiados intentos. Prueba de nuevo en <span id="lockout-seconds">{{ $lockoutSeconds }}</span> segundos.</div></div>
    @elseif($errors->any())
      <div class="form-group"><div class="error">{{ $errors->first() }}</div></div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}" id="login-form"
          x-data="{ submitting: false, showPassword: false }" x-on:submit="submitting = true">
      @csrf
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
               autocomplete="username" {{ $lockoutSeconds ? 'disabled' : '' }}>
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="admin-login-password-wrap">
          <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                 autocomplete="current-password" {{ $lockoutSeconds ? 'disabled' : '' }}>
          <button type="button" class="admin-login-password-toggle" @click="showPassword = !showPassword"
                  :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" tabindex="-1">
            <svg x-show="!showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/></svg>
            <svg x-show="showPassword" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.24 4.24M9.88 4.6A11 11 0 0 1 12 4.4c7 0 11 7 11 7a13.4 13.4 0 0 1-3.14 3.85M6.6 6.6C3.7 8.3 2 12 2 12s4 7.4 11 7.4c1.35 0 2.6-.24 3.73-.66" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>
      <label class="admin-login-remember">
        <input type="checkbox" name="remember">
        <span>Recordarme en este dispositivo</span>
      </label>
      <button type="submit" class="btn btn-primary" id="login-submit" :disabled="submitting" {{ $lockoutSeconds ? 'disabled' : '' }}>
        <span class="btn-spinner" x-show="submitting" x-cloak></span>
        <span x-text="submitting ? 'Ingresando…' : 'Ingresar'"></span>
      </button>
    </form>
  </div>
</div>

@if($lockoutSeconds)
<script>
  (function() {
    var remaining = {{ (int) $lockoutSeconds }};
    var secondsEl = document.getElementById('lockout-seconds');
    var timer = setInterval(function() {
      remaining -= 1;
      if (remaining <= 0) {
        clearInterval(timer);
        document.getElementById('lockout-msg').textContent = 'Ya puedes volver a intentar.';
        document.getElementById('email').disabled = false;
        document.getElementById('password').disabled = false;
        document.getElementById('login-submit').disabled = false;
        return;
      }
      secondsEl.textContent = remaining;
    }, 1000);
  })();
</script>
@endif

</body>
</html>
