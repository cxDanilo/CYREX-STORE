<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('status', 'active')->with(['category', 'variants']);
        $activeCategory = null;

        if ($request->filled('category')) {
            $activeCategory = Category::where('slug', $request->category)->first();
            if ($activeCategory) {
                $ids = $activeCategory->parent_id
                    ? [$activeCategory->id]
                    : $activeCategory->children()->pluck('id')->push($activeCategory->id);
                $query->whereIn('category_id', $ids);
            }
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }

        $products = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        // Una imagen al azar (entre las que el admin cargó en Ajustes)
        // como fondo del título — cambia en cada visita, no mientras se
        // navega la página.
        $bannerImages = json_decode(Setting::get('shop_banner_images', '[]'), true) ?: [];
        $shopBannerImage = $bannerImages ? asset('uploads/'.$bannerImages[array_rand($bannerImages)]) : null;

        return view('shop', compact('products', 'activeCategory', 'shopBannerImage'));
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
