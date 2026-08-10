<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\PcBuilderOption;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('status', 'active')->with(['category', 'variants']);
        $activeCategory = null;
        $filterField = null;
        $filterLabel = null;
        $filterOptions = [];

        if ($request->filled('category')) {
            $activeCategory = Category::where('slug', $request->category)->first();
            if ($activeCategory) {
                $ids = $activeCategory->parent_id
                    ? [$activeCategory->id]
                    : $activeCategory->children()->pluck('id')->push($activeCategory->id);
                $query->whereIn('category_id', $ids);

                [$filterField, $filterLabel, $filterOptions] = $this->resolveShopFilter($activeCategory->component_type);
            }
        }

        if ($filterField && $request->filled('attr')) {
            $query->where('compat->'.$filterField, $request->attr);
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }

        $this->applySort($query, $request->get('orden', 'predeterminado'));

        $products = $query->paginate(12)->withQueryString();

        // Una imagen al azar (entre las que el admin cargó en Ajustes)
        // como fondo del título — cambia en cada visita, no mientras se
        // navega la página.
        $bannerImages = json_decode(Setting::get('shop_banner_images', '[]'), true) ?: [];
        $shopBannerImage = $bannerImages ? asset('uploads/'.$bannerImages[array_rand($bannerImages)]) : null;

        return view('shop', compact('products', 'activeCategory', 'shopBannerImage', 'filterField', 'filterLabel', 'filterOptions'));
    }

    /**
     * Busca, en los campos de compat del tipo de componente/atributo de
     * la categoría, el (único) campo marcado 'shop_filter' => true en
     * config/pc_builder.php — ese es el que se ofrece como filtro acá.
     * Funciona igual para piezas de PC (ej. Procesador → Plataforma) que
     * para categorías "solo atributo" (ej. Teclado → Tipo de switch).
     *
     * @return array{0: ?string, 1: ?string, 2: array<string,string>}
     */
    private function resolveShopFilter(?string $componentType): array
    {
        if (! $componentType) {
            return [null, null, []];
        }

        foreach (config("pc_builder.fields.$componentType", []) as $key => $field) {
            if (empty($field['shop_filter'])) {
                continue;
            }

            $options = $field['options'] ?? [];
            if (is_string($options) && str_starts_with($options, 'dynamic:')) {
                $options = PcBuilderOption::optionsFor(substr($options, strlen('dynamic:')));
            }

            return [$key, $field['label'] ?? $key, $options];
        }

        return [null, null, []];
    }

    /**
     * price se guarda en la moneda propia de cada producto (USD o BOB) —
     * ordenar por el número crudo mezclaría ambas monedas de forma
     * incorrecta, así que se normaliza a USD dentro del propio ORDER BY.
     */
    private function applySort(Builder $query, string $sort): void
    {
        $rate = ExchangeRate::current();

        match ($sort) {
            'precio_asc' => $query->orderByRaw("(CASE WHEN currency = 'BOB' THEN price / ? ELSE price END) ASC", [$rate]),
            'precio_desc' => $query->orderByRaw("(CASE WHEN currency = 'BOB' THEN price / ? ELSE price END) DESC", [$rate]),
            'recientes' => $query->orderByDesc('created_at'),
            'nombre_az' => $query->orderBy('name'),
            default => $query->orderBy('id'),
        };
    }

    public function suggest(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $products = Product::where('status', 'active')
            ->where('name', 'like', '%'.$term.'%')
            ->with('category')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return response()->json([
            'results' => $products->map(fn ($p) => [
                'name' => $p->name,
                'category' => $p->category->name,
                'url' => route('product.show', $p->slug),
                'image' => $p->image_url,
                'price' => $p->currency === 'USD'
                    ? '$'.number_format($p->price, 2)
                    : 'Bs '.number_format($p->price, 2),
            ]),
        ]);
    }

    public function show(string $slug)
    {
        $rate = ExchangeRate::current();
        $product = Product::where('slug', $slug)->with(['variants', 'category'])->firstOrFail();

        // Un producto "privado" no debe ser visible por nadie del
        // público general aunque tenga el link directo — solo un admin
        // logueado puede entrar a previsualizarlo antes de publicarlo.
        if ($product->status !== 'active' && ! (auth()->check() && auth()->user()->isAdmin())) {
            abort(404);
        }

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with('variants')
            ->take(4)
            ->get();

        $currencyMode = Setting::get('currency_mode', 'both');
        $defaultCurrency = Setting::get('default_currency', 'USD');

        return view('product', compact('rate', 'product', 'related', 'currencyMode', 'defaultCurrency'));
    }
}
