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
    /**
     * Solo el ID de YouTube, sin armar ninguna URL -- lo usa el bloque
     * "Hero con video" para instanciar el reproductor via la API de
     * YouTube en vez de un <iframe src="..."> estático (necesario para
     * poder tapar el destello de carga y forzar que retome el play
     * cuando la pestaña vuelve a estar visible).
     */
    public static function youtubeId(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m)
            || preg_match('~youtube\.com/watch\?v=([A-Za-z0-9_-]{6,})~', $url, $m)
            || preg_match('~youtube\.com/embed/([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }

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

    /**
     * Variante para video de fondo: mudo, en loop, sin controles ni marca
     * visible. Solo tiene sentido para YouTube/Vimeo — un archivo de video
     * directo (mp4) se renderiza aparte con la etiqueta <video> nativa,
     * que ya soporta esos mismos atributos sin necesitar esto.
     *
     * $startSeconds (ver parseStartSeconds()) es el punto desde el que
     * arranca Y desde el que vuelve a empezar cada vez que repite en loop
     * — no es solo un salto inicial. En YouTube esto sale gratis: el
     * truco de loop=1+playlist={id} hace que cada vuelta reinicie la URL
     * completa, start incluido. En Vimeo NO hay forma de lograr lo mismo
     * solo con parámetros de URL (su equivalente, #t=, es nada más que
     * un salto inicial de una sola vez — cada loop vuelve a 0 igual), así
     * que a propósito no se le aplica acá para no ofrecer una función a
     * medias sin avisar.
     */
    public static function backgroundEmbedUrl(string $url, int $startSeconds = 0): ?string
    {
        $url = trim($url);

        if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m)
            || preg_match('~youtube\.com/watch\?v=([A-Za-z0-9_-]{6,})~', $url, $m)
            || preg_match('~youtube\.com/embed/([A-Za-z0-9_-]{6,})~', $url, $m)) {
            $id = $m[1];
            $startParam = $startSeconds > 0 ? "&start={$startSeconds}" : '';

            return "https://www.youtube.com/embed/{$id}?autoplay=1&mute=1&loop=1&playlist={$id}&controls=0&showinfo=0&modestbranding=1&rel=0&disablekb=1&playsinline=1{$startParam}";
        }

        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?autoplay=1&muted=1&loop=1&background=1';
        }

        return null;
    }

    /**
     * "1:30", "90" o vacío/inválido (-> 0, sin offset) — pensado para que
     * el admin escriba minuto:segundo como lo piensa, no segundos totales
     * a mano.
     */
    public static function parseStartSeconds(?string $raw): int
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return 0;
        }

        if (preg_match('~^(\d+):([0-5]?\d)$~', $raw, $m)) {
            return ((int) $m[1]) * 60 + (int) $m[2];
        }

        if (preg_match('~^\d+$~', $raw)) {
            return (int) $raw;
        }

        return 0;
    }
}
