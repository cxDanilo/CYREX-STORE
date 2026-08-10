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

    /**
     * Recorta el margen transparente sobrante alrededor del contenido real
     * de un PNG/GIF/WebP — pensado para logos de marcas subidos con
     * cantidades de "aire" muy distintas entre sí, que hacían que, aunque
     * se normalice la caja donde se muestran (mismo alto para todos), el
     * dibujo visible dentro de cada caja quedara de tamaño bien distinto.
     * No hace nada si el archivo no tiene canal alfa (ej. JPEG) o si ya
     * está bien ajustado — solo se llama para campos marcados 'trim' en
     * config/cms_blocks.php, nunca en la subida genérica de la Biblioteca.
     */
    public static function trimTransparentPadding(string $absolutePath, string $mimeType): void
    {
        if (! in_array($mimeType, ['image/png', 'image/gif', 'image/webp'], true) || ! file_exists($absolutePath)) {
            return;
        }

        $image = self::load($absolutePath, $mimeType);

        if (! $image) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $left = $width;
        $right = -1;
        $top = $height;
        $bottom = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // GD guarda el alfa en 7 bits: 0 = opaco, 127 = totalmente
                // transparente. 120 deja un pequeño margen para píxeles casi
                // transparentes del antialiasing del borde del logo.
                $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;
                if ($alpha < 120) {
                    if ($x < $left) $left = $x;
                    if ($x > $right) $right = $x;
                    if ($y < $top) $top = $y;
                    if ($y > $bottom) $bottom = $y;
                }
            }
        }

        if ($right < $left || $bottom < $top) {
            imagedestroy($image);

            return;
        }

        $pad = (int) round(max($right - $left, $bottom - $top) * 0.04);
        $left = max(0, $left - $pad);
        $top = max(0, $top - $pad);
        $right = min($width - 1, $right + $pad);
        $bottom = min($height - 1, $bottom + $pad);

        $newWidth = $right - $left + 1;
        $newHeight = $bottom - $top + 1;

        if ($newWidth === $width && $newHeight === $height) {
            imagedestroy($image);

            return;
        }

        $cropped = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefill($cropped, 0, 0, $transparent);
        imagecopy($cropped, $image, 0, 0, $left, $top, $newWidth, $newHeight);

        self::save($cropped, $absolutePath, $mimeType);

        imagedestroy($cropped);
        imagedestroy($image);
    }

    private static function load(string $path, string $mime)
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };

        // Sin esto, re-guardar un PNG/WebP/GIF con transparencia (vía
        // save(), sin pasar por resize() que sí las seteaba) pierde el
        // canal alfa y el fondo transparente se vuelve negro sólido.
        if ($image && in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
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
