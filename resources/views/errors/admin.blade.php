<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Error {{ $code }} — Cyrex Store Admin</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>

<div class="admin-login-wrap">
  <div class="admin-login-card" style="text-align:center;">
    <div class="admin-login-logo">CYREX<span>.</span></div>
    <div class="mono" style="color:var(--gold);font-size:13px;letter-spacing:.05em;margin:18px 0 8px;">ERROR {{ $code }}</div>
    <p style="color:var(--text-secondary);font-size:14.5px;line-height:1.6;margin-bottom:28px;">{{ $message }}</p>
    <a href="{{ auth()->check() ? route('admin.dashboard') : route('admin.login') }}" class="btn btn-primary" style="width:100%;display:flex;justify-content:center;">Volver al panel</a>
  </div>
</div>

</body>
</html>
