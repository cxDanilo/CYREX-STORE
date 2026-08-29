<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="15">
<title>Cyrex Store — Volvemos enseguida</title>
<style>
  :root{
    --bg:#080808;--bg-elevated:#131313;
    --gold:#ffd900;--gold-dim:#3a3300;
    --text-primary:#f5f5f3;--text-secondary:#8e8e93;
    --border:rgba(255,255,255,.08);
  }
  *{box-sizing:border-box;}
  html,body{height:100%;}
  body{
    margin:0;display:flex;align-items:center;justify-content:center;
    background:var(--bg);color:var(--text-primary);
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
    text-align:center;padding:24px;
  }
  .wrap{max-width:440px;}
  .logo{
    max-width:220px;width:100%;height:auto;
    margin-bottom:40px;
  }
  h1{
    font-weight:700;font-size:clamp(22px,4vw,28px);
    letter-spacing:-0.01em;line-height:1.25;margin:0 0 12px;
  }
  p{
    color:var(--text-secondary);font-size:15px;line-height:1.6;
    margin:0 0 32px;
  }
  .dots{display:inline-flex;gap:6px;}
  .dots span{
    width:6px;height:6px;border-radius:50%;background:var(--gold);
    animation:bounce 1.1s ease-in-out infinite;
  }
  .dots span:nth-child(2){animation-delay:.15s;}
  .dots span:nth-child(3){animation-delay:.3s;}
  @keyframes bounce{
    0%,80%,100%{transform:translateY(0);opacity:.4;}
    40%{transform:translateY(-6px);opacity:1;}
  }
  @media (prefers-reduced-motion:reduce){
    .dots span{animation:none;opacity:1;}
  }
</style>
</head>
<body>
  <div class="wrap">
    <img src="{{ asset('images/logo-horizontal.png') }}" alt="Cyrex Store" class="logo">
    <h1>Volvemos enseguida</h1>
    <p>Estamos actualizando la tienda — esta página se refresca sola.</p>
    <div class="dots" aria-hidden="true"><span></span><span></span><span></span></div>
  </div>
</body>
</html>
