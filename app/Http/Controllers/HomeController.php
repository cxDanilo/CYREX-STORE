<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $rate = ExchangeRate::current();
        $categories = Category::parents()->with('children')->get();
        $featured = Product::where('status', 'active')->latest()->take(6)->get();

        return view('home', compact('rate', 'categories', 'featured'));
    }
}
