<?php

namespace App\Support;

// Sin librería de detección de dispositivos en el proyecto (composer.json
// no trae ninguna) — alcanza con regex simples para navegador/SO, que es
// lo que se necesita para el desglose de Analítica. El orden de los
// checks importa: Edge/Opera/Samsung Internet meten "Chrome" en su propio
// user-agent, y Chrome mete "Safari" en el suyo.
class UserAgentParser
{
    public static function browser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') || str_contains($userAgent, 'Edge/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Otro',
        };
    }

    public static function os(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/', $userAgent) => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            (bool) preg_match('/Macintosh|Mac OS X/', $userAgent) => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Otro',
        };
    }
}
