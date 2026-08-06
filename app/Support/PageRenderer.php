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

        return View::make($definition['view'], ['data' => $merged])->render();
    }
}
