<?php

namespace App\Support;

use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class PageRenderer
{
    public static function render(Page $page): string
    {
        return $page->blocks
            ->map(fn (PageBlock $block) => static::renderBlock($block))
            ->implode('');
    }

    public static function renderBlock(PageBlock $block): string
    {
        return static::renderBlockData($block->type, $block->data ?? [], $block->id);
    }

    /**
     * Renderiza un bloque a partir de su tipo y datos crudos, sin necesitar
     * un PageBlock persistido. Es el mismo camino de render que usa el sitio
     * público — lo reutiliza el endpoint de preview del editor para que lo
     * que se ve mientras se edita sea exactamente el HTML final, nunca una
     * aproximación aparte.
     */
    public static function renderBlockData(string $type, array $data, ?int $blockId = null): string
    {
        $definition = config("cms_blocks.{$type}");

        if (! $definition) {
            Log::warning("PageRenderer: tipo de bloque '{$type}' no está registrado en config/cms_blocks.php".($blockId ? " (bloque #{$blockId})" : '').'.');

            return '';
        }

        if (! View::exists($definition['view'])) {
            Log::warning("PageRenderer: la vista '{$definition['view']}' del bloque tipo '{$type}' no existe".($blockId ? " (bloque #{$blockId})" : '').'.');

            return '';
        }

        $merged = array_merge($definition['defaults'] ?? [], $data);

        try {
            return View::make($definition['view'], ['data' => $merged])->render();
        } catch (\Throwable $e) {
            // Un bloque roto (o una vista que falla al compilar en ese
            // momento puntual) no puede tirar abajo la página entera —
            // esto se vio en producción: el bloque "marcas" falló y se
            // llevó puesta cualquier página armada con este motor de
            // bloques (la home incluida). Mejor mostrar la página sin
            // ese bloque que un 500 completo.
            Log::error("PageRenderer: el bloque tipo '{$type}' falló al renderizar".($blockId ? " (bloque #{$blockId})" : '').': '.$e->getMessage());

            return '';
        }
    }
}
