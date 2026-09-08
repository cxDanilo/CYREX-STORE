<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DiscountGroup;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Setting;
use App\Support\AdminCurrencyPref;
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

                // Además de su categoría real (category_id) y las
                // adicionales que le hayan marcado a mano, si esta es la
                // categoría elegida en Ajustes como "automática de
                // ofertas", también entra cualquier producto con oferta
                // activa ahora mismo — sin que nadie tenga que agregarlo
                // ni sacarlo cuando la oferta termine.
                $isAutoPromoCategory = (int) Setting::get('auto_promo_category_id', '') === $activeCategory->id;

                $query->where(function (Builder $q) use ($ids, $isAutoPromoCategory) {
                    $q->whereIn('category_id', $ids)
                        ->orWhereHas('categories', fn (Builder $q2) => $q2->whereIn('categories.id', $ids));

                    if ($isAutoPromoCategory) {
                        $q->orWhere(fn (Builder $q3) => $q3->hasActiveOffer());
                    }
                });

                [$filterField, $filterLabel, $filterOptions] = $this->resolveShopFilter($activeCategory->component_type);

                if ($filterField) {
                    $filterOptions = $this->filterOptionsWithProducts($filterOptions, $filterField, $ids, $isAutoPromoCategory);
                }
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
        $rate = ExchangeRate::current();
        $forceBob = AdminCurrencyPref::forceBob();

        // Si la categoría activa tiene su propio banner cargado (Admin →
        // Categorías), se usa ese — si no, cae a una imagen al azar entre
        // las genéricas de Ajustes (cambia en cada visita, no mientras se
        // navega la página).
        if ($activeCategory?->banner_image_url) {
            $shopBannerImage = $activeCategory->banner_image_url;
        } else {
            $bannerImages = json_decode(Setting::get('shop_banner_images', '[]'), true) ?: [];
            $shopBannerImage = $bannerImages ? asset('uploads/'.$bannerImages[array_rand($bannerImages)]) : null;
        }

        // Pedido AJAX (orden/filtro/página cambiados sin recargar, ver
        // public/js/shop-ajax.js): solo el fragmento de resultados, no la
        // página completa — activeCategory/shopBannerImage se pasan igual
        // porque el fragmento también decide si mostrar "Ver todo el
        // catálogo ×" en base a activeCategory.
        if ($request->ajax()) {
            return view('partials.shop-results', compact('products', 'activeCategory', 'filterField', 'filterLabel', 'filterOptions', 'rate', 'forceBob'));
        }

        return view('shop', compact('products', 'activeCategory', 'shopBannerImage', 'filterField', 'filterLabel', 'filterOptions', 'rate', 'forceBob'));
    }

    /**
     * Busca, en los campos de compat del tipo de componente/atributo de
     * la categoría, el (único) campo marcado 'shop_filter' => true — ya
     * sea definido en config/pc_builder.php o agregado desde Admin →
     * Atributos personalizados (ver PcBuilderFields::resolved(), que
     * combina ambas fuentes). Funciona igual para piezas de PC (ej.
     * Procesador → Plataforma) que para categorías "solo atributo" (ej.
     * Teclado → Tipo de switch).
     *
     * @return array{0: ?string, 1: ?string, 2: array<string,string>}
     */
    private function resolveShopFilter(?string $componentType): array
    {
        if (! $componentType) {
            return [null, null, []];
        }

        foreach (\App\Support\PcBuilderFields::resolved()[$componentType] ?? [] as $key => $field) {
            if (empty($field['shop_filter'])) {
                continue;
            }

            return [$key, $field['label'] ?? $key, $field['options'] ?? []];
        }

        return [null, null, []];
    }

    /**
     * Las opciones del filtro (ej. "80 Plus Bronze/Silver/Gold...") vienen
     * de config/pc_builder.php o de un campo personalizado — una lista
     * fija que no sabe nada de qué productos existen. Sin este filtro se
     * veían pastillas para certificaciones/valores que ningún producto de
     * la categoría tiene cargado, un callejón sin salida para quien las
     * toca. Se deja solo el subconjunto con al menos un producto activo.
     *
     * @param  array<string,string>  $options
     * @param  \Illuminate\Support\Collection<int,int>|array<int>  $categoryIds
     * @return array<string,string>
     */
    private function filterOptionsWithProducts(array $options, string $filterField, $categoryIds, bool $isAutoPromoCategory): array
    {
        $valuesInUse = Product::where('status', 'active')
            ->where(function (Builder $q) use ($categoryIds, $isAutoPromoCategory) {
                $q->whereIn('category_id', $categoryIds)
                    ->orWhereHas('categories', fn (Builder $q2) => $q2->whereIn('categories.id', $categoryIds));

                if ($isAutoPromoCategory) {
                    $q->orWhere(fn (Builder $q3) => $q3->hasActiveOffer());
                }
            })
            ->get(['compat'])
            ->map(fn (Product $p) => $p->compat[$filterField] ?? null)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (string) $v)
            ->unique();

        return array_intersect_key($options, array_flip($valuesInUse->all()));
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
            default => $query->orderByDesc('created_at'),
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

        $forceBob = AdminCurrencyPref::forceBob();
        $rate = $forceBob ? ExchangeRate::current() : null;

        return response()->json([
            'results' => $products->map(fn ($p) => [
                'name' => $p->name,
                'category' => $p->category->name,
                'url' => route('product.show', $p->slug),
                'image' => $p->image_url,
                'price' => $forceBob
                    ? 'Bs '.number_format($p->effectivePriceInBob($rate), 2)
                    : ($p->currency === 'USD'
                        ? '$'.number_format($p->effectivePrice(), 2)
                        : 'Bs '.number_format($p->effectivePrice(), 2)),
            ]),
        ]);
    }

    public function show(string $slug)
    {
        $rate = ExchangeRate::current();
        $product = Product::where('slug', $slug)->with(['variants', 'category', 'categories'])->firstOrFail();

        // Un producto "privado" no debe ser visible por nadie del
        // público general aunque tenga el link directo — solo un admin
        // logueado puede entrar a previsualizarlo antes de publicarlo.
        if ($product->status !== 'active' && ! (auth()->check() && auth()->user()->isAdmin())) {
            abort(404);
        }

        // Mismo criterio que la tienda: comparten categoría si tienen la
        // misma real, o si cualquiera de las adicionales de este producto
        // coincide con la real o las adicionales del otro.
        $categoryIds = $product->categories->pluck('id')->push($product->category_id)->unique()->values();

        $related = Product::where(function (Builder $q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                    ->orWhereHas('categories', fn (Builder $q2) => $q2->whereIn('categories.id', $categoryIds));
            })
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with('variants')
            ->take(4)
            ->get();

        $currencyMode = Setting::get('currency_mode', 'both');
        $defaultCurrency = Setting::get('default_currency', 'USD');
        $activeDiscountGroup = DiscountGroup::first();
        $forceBob = AdminCurrencyPref::forceBob();

        return view('product', compact('rate', 'product', 'related', 'currencyMode', 'defaultCurrency', 'activeDiscountGroup', 'forceBob'));
    }
}
