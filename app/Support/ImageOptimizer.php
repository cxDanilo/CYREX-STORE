<?php

namespace App\Support;

/**
 * Procesamiento de imágenes de la biblioteca multimedia usando GD nativo
 * (sin dependencia de Composer nueva — GD ya viene con PHP y soporta WebP
 * en este entorno, verificado antes de construir esto).
 *
 * Si GD no está disponible o el formato no es uno soportado (ej. SVG),
 * process() devuelve rutas nulas sin lanzar excepción — la subida en sí
 * sigue funcionando, solo sin optimización/WebP/miniatura para ese archivo.
 */
class ImageOptimizer
{
    private const MAX_DIMENSION = 2000;
    private const THUMB_DIMENSION = 400;
    private const QUALITY = 82;

    private const SUPPORTED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public static function isSupported(string $mimeType): bool
    {
        return function_exists('gd_info') && in_array($mimeType, self::SUPPORTED_MIMES, true);
    }

    /**
     * @return array{webp: ?string, thumb: ?string} rutas RELATIVAS (mismo
     *         formato que $relativePath) de los archivos generados, o null
     *         si no se pudieron generar.
     */
    public static function process(string $absolutePath, string $relativePath, string $mimeType): array
    {
        if (! self::isSupported($mimeType) || ! file_exists($absolutePath)) {
            return ['webp' => null, 'thumb' => null];
        }

        $image = self::load($absolutePath, $mimeType);

        if (! $image) {
            return ['webp' => null, 'thumb' => null];
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            $resized = self::resize($image, self::MAX_DIMENSION);
            imagedestroy($image);
            $image = $resized;
        }

        // Re-guarda el original (ya sea redimensionado o no) a la calidad
        // objetivo — esto es lo que efectivamente "optimiza" el peso del
        // archivo que ya se subió, no solo genera derivados nuevos.
        self::save($image, $absolutePath, $mimeType);

        $webpAbsolute = self::withExtension($absolutePath, 'webp');
        $webpOk = @imagewebp($image, $webpAbsolute, self::QUALITY);

        $thumbAbsolute = self::withPrefix($absolutePath, 'thumb_');
        $thumb = self::resize($image, self::THUMB_DIMENSION);
        $thumbOk = self::save($thumb, $thumbAbsolute, $mimeType);
        imagedestroy($thumb);
        imagedestroy($image);

        return [
            'webp' => $webpOk ? self::withExtension($relativePath, 'webp') : null,
            'thumb' => $thumbOk ? self::withPrefix($relativePath, 'thumb_') : null,
        ];
    }

    /**
     * Reprocesa una imagen ya existente in place — usado al "reemplazar
     * archivo" de un ítem de la biblioteca: regenera webp/thumb en las
     * MISMAS rutas relativas, para que la URL pública nunca cambie.
     */
    public static function reprocess(string $absolutePath, string $relativePath, string $mimeType): array
    {
        return self::process($absolutePath, $relativePath, $mimeType);
    }

    private static function load(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };
    }

    private static function resize($image, int $maxDimension)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min($maxDimension / $width, $maxDimension / $height, 1);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $resized;
    }

    private static function save($image, string $path, string $mime): bool
    {
        return (bool) match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, self::QUALITY),
            'image/png' => imagepng($image, $path, 6),
            'image/gif' => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, self::QUALITY),
            default => false,
        };
    }

    private static function withExtension(string $path, string $extension): string
    {
        return preg_replace('/\.[^.\/]+$/', '.'.$extension, $path);
    }

    private static function withPrefix(string $path, string $prefix): string
    {
        $dir = dirname($path);
        $base = basename($path);

        return ($dir === '.' ? '' : $dir.'/').$prefix.$base;
    }
}
