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
  .mark{
    display:inline-flex;align-items:center;justify-content:center;
    width:56px;height:56px;border-radius:16px;
    background:var(--gold-dim);border:1px solid var(--border);
    margin-bottom:28px;
  }
  .mark svg{width:26px;height:26px;}
  .logo{
    font-weight:800;font-size:19px;letter-spacing:-0.01em;
    margin-bottom:36px;
  }
  .logo span{color:var(--gold);}
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
    <div class="mark">
      <svg viewBox="0 0 24 24" fill="none"><path d="M4 7l8-4 8 4v10l-8 4-8-4V7z" stroke="#ffd900" stroke-width="1.5" stroke-linejoin="round"/><path d="M4 7l8 4 8-4M12 11v10" stroke="#ffd900" stroke-width="1.5" stroke-linejoin="round"/></svg>
    </div>
    <div class="logo">CYREX <span>STORE</span></div>
    <h1>Volvemos enseguida</h1>
    <p>Estamos actualizando la tienda. Esto no debería tardar más de un minuto — esta página se refresca sola.</p>
    <div class="dots" aria-hidden="true"><span></span><span></span><span></span></div>
  </div>
</body>
</html>
