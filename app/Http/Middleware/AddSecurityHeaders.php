<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// HTTPS ya se fuerza (redirect en el hosting) y las cookies van Secure,
// pero sin esta cabecera un visitante que todavía no cargó el sitio (o
// con la caché de HSTS vencida) sigue siendo vulnerable a un downgrade
// a HTTP en el primer salto (ej. red Wi-Fi hostil). Ver auditoría de
// seguridad, hallazgo F6. Solo se manda sobre HTTPS real: emitirla
// sobre HTTP no hace nada (los navegadores la ignoran ahí) y evita
// mandarla en local/desarrollo por accidente.
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // El sitio no usa ninguno de estos permisos de navegador (sin
        // cámara/micrófono/geolocalización en ningún lado) — de-opt
        // explícito, no cambia nada visible. Ver auditoría de
        // seguridad, hallazgo H6.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
        );

        return $response;
    }
}
