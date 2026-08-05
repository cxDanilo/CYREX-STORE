<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $rate = ExchangeRate::current();
        $categories = Category::parents()->with('children')->get();

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

        $products = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        return view('shop', compact('rate', 'categories', 'products'));
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

        return view('product', compact('rate', 'product', 'related'));
    }
}
