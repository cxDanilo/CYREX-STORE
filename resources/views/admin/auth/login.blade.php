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
</head>
<body>

<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div class="admin-login-logo">CYREX<span>.</span></div>
    <div class="admin-login-sub">Acceso administradores</div>

    @if($lockoutSeconds)
      <div class="form-group"><div class="error" id="lockout-msg">Demasiados intentos. Probá de nuevo en <span id="lockout-seconds">{{ $lockoutSeconds }}</span> segundos.</div></div>
    @elseif($errors->any())
      <div class="form-group"><div class="error">{{ $errors->first() }}</div></div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}" id="login-form">
      @csrf
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus {{ $lockoutSeconds ? 'disabled' : '' }}>
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required {{ $lockoutSeconds ? 'disabled' : '' }}>
      </div>
      <button type="submit" class="btn btn-primary" id="login-submit" {{ $lockoutSeconds ? 'disabled' : '' }}>Ingresar</button>
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
        document.getElementById('lockout-msg').textContent = 'Ya podés volver a intentar.';
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
