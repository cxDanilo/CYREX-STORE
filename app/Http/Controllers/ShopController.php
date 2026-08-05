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
        $query = Product::where('status', 'active')->with('category');

        if ($request->filled('category')) {
            $cat = Category::where('slug', $request->category)->first();
            if ($cat) {
                $ids = $cat->parent_id
                    ? [$cat->id]
                    : $cat->children()->pluck('id')->push($cat->id);
                $query->whereIn('category_id', $ids);
            }
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }

        $products = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        return view('shop', compact('products'));
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

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->take(4)
            ->get();

        $currencyMode = Setting::get('currency_mode', 'both');
        $defaultCurrency = Setting::get('default_currency', 'USD');

        return view('product', compact('rate', 'product', 'related', 'currencyMode', 'defaultCurrency'));
    }
}
