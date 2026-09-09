<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitiza HTML de campos que SÍ deberían admitir formato básico (ej. la
 * descripción de un producto) pero NUNCA deberían poder ejecutar código —
 * a diferencia del bloque CMS "HTML libre", que es intencionalmente crudo
 * y sin sanitizar (ver config/cms_blocks.php) y ahora solo lo puede tocar
 * un admin (ver auditoría de seguridad, hallazgo F1).
 *
 * HTMLPurifier en vez de un strip_tags/regex a mano: un filtro casero es
 * la forma más común de terminar con un XSS "arreglado" que en realidad
 * sigue teniendo un bypass (ej. strip_tags no saca atributos onXxx= de
 * las etiquetas que sí deja pasar).
 */
class HtmlSanitizer
{
    public static function description(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('app/htmlpurifier-cache'));
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's',
            'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'table', 'thead', 'tbody', 'tr', 'td', 'th',
            'a[href]', 'span', 'div',
        ]));
        // Nunca links javascript:/data: — solo esquemas de verdad.
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.TargetBlank', true);

        return (new HTMLPurifier($config))->purify($html);
    }
}
