<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Edición rápida desde la página pública del producto (visible solo para
 * admins logueados) — a propósito solo expone nombre, precio, descripción
 * e imagen. Todo lo demás (categoría, stock, variantes, SKU) sigue
 * viviendo únicamente en /admin/productos.
 */
class ProductQuickEditController extends Controller
{
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $before = $product->getAttributes();

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('uploads')->delete($product->image);
            }

            $file = $request->file('image');
            $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
            $file->storeAs('products', $filename, 'uploads');
            $data['image'] = 'products/'.$filename;
        }

        $product->update($data);
        ProductActivityLog::logFieldChanges($product, $before);

        return response()->json([
            'name' => $product->name,
            'price' => (float) $product->price,
            'description' => $product->description,
            'image_url' => $product->image_url,
        ]);
    }
}
