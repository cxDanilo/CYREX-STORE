<?php

namespace App\Support;

/**
 * Convierte una URL de YouTube/Vimeo en una URL de embed segura.
 * Existe específicamente para que el bloque "Video" nunca acepte código de
 * embed pegado (HTML/iframe libre) — solo un link, que se valida contra un
 * patrón conocido antes de construir el iframe.
 */
class VideoEmbed
{
    public static function embedUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m)
            || preg_match('~youtube\.com/watch\?v=([A-Za-z0-9_-]{6,})~', $url, $m)
            || preg_match('~youtube\.com/embed/([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
