<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

// Único lugar del sitio que resuelve "qué número de WhatsApp le toca ver a
// ESTE visitante" — antes cada uno de los ~11 puntos que mostraban un botón
// de WhatsApp llamaba a Setting::get('whatsapp_number', ...) por su cuenta,
// así que agregar el enrutamiento por referido ahí adentro (en vez de acá,
// un solo lugar) hubiera significado repetir la misma lógica 11 veces y
// arriesgarse a que alguna quedara afuera.
class ReferralRouter
{
    public const COOKIE_NAME = 'ref';

    public const COOKIE_DAYS = 15;

    // Nunca puede devolver vacío: sin cookie, con un código que ya no
    // matchea a nadie, o un vendedor sin número personal cargado, siempre
    // cae al número general de siempre — el botón no se puede romper.
    public static function whatsappNumber(): string
    {
        $default = Setting::get('whatsapp_number', '59177947379');

        $code = request()?->cookie(self::COOKIE_NAME);
        if (! $code) {
            return $default;
        }

        return User::where('ref_code', $code)->value('whatsapp_number') ?: $default;
    }
}
