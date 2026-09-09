<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

// Modo "kiosco" para las pantallas físicas del local: ?demo=1 en
// cualquier URL prende un recorrido automático que navega solo por el
// sitio (ver public/js/demo-mode.js). Se recuerda en una cookie de un
// año (no solo localStorage) porque cada paso del recorrido es una
// navegación real de página completa, no AJAX — el servidor necesita
// saber que está en modo demo para EXCLUIR ese tráfico de las
// estadísticas reales (Admin > Analítica) y de Google Analytics, ya
// que si no una pantalla en loop 24/7 infla esos números sin parar.
class DemoMode
{
    public const COOKIE = 'cyrex_demo';

    public static function active(Request $request): bool
    {
        return $request->query('demo') === '1' || $request->cookie(self::COOKIE) === '1';
    }

    public static function syncCookie(Request $request): void
    {
        if ($request->query('demo') === '0') {
            Cookie::queue(Cookie::forget(self::COOKIE));
        } elseif ($request->query('demo') === '1') {
            Cookie::queue(self::COOKIE, '1', 60 * 24 * 365);
        }
    }
}
