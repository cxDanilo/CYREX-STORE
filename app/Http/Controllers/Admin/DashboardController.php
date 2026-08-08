<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductActivityLog;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'productos_activos' => Product::where('status', 'active')->count(),
            'productos_inactivos' => Product::where('status', 'inactive')->count(),
            'categorias' => Category::count(),
            'usuarios' => User::count(),
            'paginas_publicadas' => Page::where('status', 'published')->count(),
        ];

        $sinStock = Product::where('status', 'active')->where('stock', 0)->orderBy('name')->limit(8)->get();
        $stockBajo = Product::where('status', 'active')->where('stock', '>', 0)->where('stock', '<=', 5)->orderBy('stock')->limit(8)->get();
        $actividadReciente = ProductActivityLog::orderByDesc('created_at')->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'sinStock', 'stockBajo', 'actividadReciente'));
    }
}
