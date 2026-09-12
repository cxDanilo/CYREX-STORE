<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::orderBy('name')->get();

        // Cuántos productos usan cada marca AHORA MISMO — brand es texto
        // libre en products (no una foreign key a esta tabla), así que se
        // cuenta por nombre en vez de una relación real.
        $counts = Product::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->selectRaw('brand, count(*) as total')
            ->groupBy('brand')
            ->pluck('total', 'brand');

        return view('admin.brands.index', compact('brands', 'counts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')],
        ]);

        Brand::create($data);

        return back()->with('status', 'Marca agregada.');
    }

    public function destroy(Brand $brand)
    {
        // No toca los productos que ya tengan esta marca cargada (brand es
        // texto libre, no una foreign key) -- solo deja de aparecer como
        // opción para elegir en productos nuevos o al editar otro.
        $brand->delete();

        return back()->with('status', 'Marca eliminada. Los productos que ya la tenían cargada no cambian.');
    }
}
