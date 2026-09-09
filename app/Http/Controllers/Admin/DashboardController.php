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

        $agotados = Product::where('status', 'active')->where('is_sold_out', true)->orderBy('name')->limit(8)->get();
        $actividadReciente = ProductActivityLog::orderByDesc('created_at')->limit(10)->get();

        // Solo la primera vez que un usuario nuevo entra al panel —
        // tour_seen_at se marca al terminarlo o saltarlo (ver
        // Admin\TourController), así que no vuelve a aparecer después.
        $startTour = auth()->user()->tour_seen_at === null;

        return view('admin.dashboard', compact('stats', 'agotados', 'actividadReciente', 'startTour'));
    }
}
