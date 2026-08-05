<?php

namespace App\Http\Controllers;

use App\Support\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        Cart::add((int) $data['product_id'], isset($data['variant_id']) ? (int) $data['variant_id'] : null);

        return back()->with('status', 'Agregado al carrito.');
    }

    public function remove(string $key)
    {
        Cart::remove($key);

        return back()->with('status', 'Producto quitado del carrito.');
    }
}
