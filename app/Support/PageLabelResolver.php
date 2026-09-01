<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Combo;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Request;

// Traduce la ruta actual a un nombre legible en español, al estilo de las
// "Páginas principales" que ya se veían en WP Statistics (nombre del
// producto/categoría/página, no la URL cruda) — separado del middleware
// para poder ubicar acá, en un solo lugar, cada ruta pública nueva que se
// agregue más adelante y quiera aparecer en Analítica.
class PageLabelResolver
{
    /**
     * @return array{label: string, productId: ?int}|null null si la ruta
     *         actual no está en la lista de páginas que se trackean.
     */
    public static function resolve(Request $request): ?array
    {
        $name = $request->route()?->getName();

        return match ($name) {
            'home' => ['label' => 'Inicio', 'productId' => null],

            'shop' => [
                'label' => $request->filled('category')
                    ? (Category::where('slug', $request->category)->value('name') ?? 'Tienda')
                    : 'Tienda',
                'productId' => null,
            ],

            'product.show' => [
                'label' => Product::where('slug', $request->route('slug'))->value('name') ?? 'Producto',
                'productId' => Product::where('slug', $request->route('slug'))->value('id'),
            ],

            'combo.show' => [
                'label' => Combo::where('slug', $request->route('slug'))->value('name') ?? 'Combo',
                'productId' => null,
            ],

            'page.show' => [
                'label' => Page::where('slug', $request->route('slug'))->value('title') ?? $request->route('slug'),
                'productId' => null,
            ],

            'pc-builder' => ['label' => 'Arma tu PC', 'productId' => null],

            default => null,
        };
    }
}
