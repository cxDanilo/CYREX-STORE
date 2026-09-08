<?php

namespace App\Support;

/**
 * Preferencia personal de un admin logueado (guardada en su sesión, no
 * en Settings) para ver el sitio público como si estuviera en "solo
 * Bs" — pensado para cuando un cliente pregunta por un producto y el
 * admin no quiere entrar a cada ficha a cambiarle la moneda a mano.
 * No afecta lo que ve el público en general, que sigue la
 * configuración real de Ajustes → Moneda.
 */
class AdminCurrencyPref
{
    const SESSION_KEY = 'admin_force_bob';

    public static function forceBob(): bool
    {
        return auth()->check() && auth()->user()->isAdmin() && session(self::SESSION_KEY, false);
    }
}
