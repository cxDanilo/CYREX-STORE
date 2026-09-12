<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;

class BrandPageController extends Controller
{
    /**
     * /marcas/{slug} -- landing editorial de una marca (hero, "por qué
     * la elegimos", tarjetas de búsqueda, comparador, etc.), armada
     * 100% desde $brand->page_data (ver Admin > Marcas > editar). Si la
     * marca no tiene la página activada (is_page_published) se
     * comporta como si no existiera -- 404, no una página vacía.
     */
    public function show(Request $request, Brand $brand)
    {
        if (! $brand->is_page_published) {
            abort(404);
        }

        $data = $brand->page_data ?? [];
        $rate = ExchangeRate::current();

        $hero = $data['hero'] ?? [];
        $heroProducts = $brand->productsByIds($hero['product_ids'] ?? []);

        $editorial = $data['editorial'] ?? [];
        $editorialProduct = ($editorial['image_product_id'] ?? null)
            ? $brand->productsByIds([$editorial['image_product_id']])->first()
            : null;

        $finderCards = collect($data['finder_cards'] ?? [])
            ->map(function ($card) use ($brand) {
                $card['products'] = $brand->productsByIds($card['product_ids'] ?? []);

                return $card;
            })
            ->filter(fn ($card) => ! empty($card['title']) && $card['products']->isNotEmpty())
            ->values();

        $exploreCategories = collect($data['explore_categories'] ?? [])
            ->filter(fn ($item) => ! empty($item['title']))
            ->map(function ($item) {
                $item['category'] = ! empty($item['category_slug'])
                    ? Category::where('slug', $item['category_slug'])->first()
                    : null;

                return $item;
            })
            ->values();

        $selection = $data['selection'] ?? [];
        $selectionProduct = ($selection['product_id'] ?? null)
            ? $brand->productsByIds([$selection['product_id']])->first()
            : null;

        $comparisonRows = collect($data['comparison']['rows'] ?? [])
            ->map(function ($row) use ($brand) {
                $row['product'] = ($row['product_id'] ?? null)
                    ? $brand->productsByIds([$row['product_id']])->first()
                    : null;

                return $row;
            })
            ->filter(fn ($row) => $row['product'])
            ->values();

        $contentItems = collect($data['content_items'] ?? [])
            ->filter(fn ($item) => ! empty($item['embed_url']))
            ->map(function ($item) {
                $item['embed'] = \App\Support\VideoEmbed::embedUrl($item['embed_url']);

                return $item;
            })
            ->filter(fn ($item) => $item['embed'])
            ->values();

        $communityPosts = collect($data['community_posts'] ?? [])
            ->filter(fn ($post) => ! empty($post['image']))
            ->values();

        // Mismo filtro por texto que ya usa ShopController (whereRaw
        // LOWER(brand) = ...) -- para el catálogo completo de la
        // sección 9 y los filtros rápidos por categoría.
        $catalogQuery = $brand->products()->with('category');
        if ($request->filled('cat')) {
            $catalogQuery->whereHas('category', fn ($q) => $q->where('slug', $request->cat));
        }
        $catalogProducts = $catalogQuery->orderByDesc('created_at')->paginate(12)->withQueryString();

        $catalogCategories = Category::whereIn('id', $brand->products()->pluck('category_id')->unique())
            ->orderBy('name')
            ->get();

        return view('brands.show', compact(
            'brand', 'rate', 'hero', 'heroProducts', 'editorial', 'editorialProduct',
            'finderCards', 'exploreCategories', 'selection', 'selectionProduct',
            'comparisonRows', 'contentItems', 'communityPosts',
            'catalogProducts', 'catalogCategories'
        ));
    }
}
