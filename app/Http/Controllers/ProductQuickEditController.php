<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductActivityLog;
use App\Support\ProductOfferManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Edición rápida desde la página pública del producto (visible solo para
 * admins logueados) — a propósito solo expone nombre, precio, descripción,
 * imagen y oferta. Todo lo demás (categoría, variantes, SKU) sigue
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
            'offer_selected' => ['nullable', 'boolean'],
            'offer_price' => ['nullable', 'numeric', 'min:0.01', 'required_if:offer_selected,1'],
            'ends_at' => ['nullable', 'date'],
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

        $product->update([
            'name' => $data['name'],
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            ...(isset($data['image']) ? ['image' => $data['image']] : []),
        ]);

        ProductOfferManager::apply(
            $product,
            $request->boolean('offer_selected'),
            $data['offer_price'] ?? null,
            $data['ends_at'] ?? null
        );

        ProductActivityLog::logFieldChanges($product, $before);

        return response()->json([
            'name' => $product->name,
            'price' => (float) $product->price,
            'description' => $product->description,
            'image_url' => $product->image_url,
            'has_active_offer' => $product->hasActiveOffer(),
            'offer_selected' => (bool) $product->offer_selected,
            'offer_price' => $product->offer_price !== null ? (float) $product->offer_price : null,
            'offer_ends_at' => $product->offerEndsAt()?->toIso8601String(),
        ]);
    }
}
