<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Support\Cart;
use App\Support\ReferralRouter;
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

        if ($request->wantsJson()) {
            return $this->cartResponse();
        }

        return back()->with('status', 'Agregado al carrito.');
    }

    public function addCombo(Request $request)
    {
        $data = $request->validate([
            'combo_id' => ['required', 'exists:combos,id'],
        ]);

        Cart::addCombo((int) $data['combo_id']);

        if ($request->wantsJson()) {
            return $this->cartResponse();
        }

        return back()->with('status', 'Combo agregado al carrito.');
    }

    public function remove(Request $request, string $key)
    {
        Cart::remove($key);

        if ($request->wantsJson()) {
            return $this->cartResponse();
        }

        return back()->with('status', 'Producto quitado del carrito.');
    }

    private function cartResponse()
    {
        $rate = ExchangeRate::current();
        $currency = Setting::get('default_currency', 'USD');
        $whatsappNumber = ReferralRouter::whatsappNumber();
        $items = Cart::items();

        $html = view('partials.cart-drawer-content', [
            'cartItems' => $items,
            'cartCurrency' => $currency,
            'cartTotal' => Cart::total($rate, $currency),
            'cartWhatsappUrl' => Cart::whatsappMessage($whatsappNumber, $rate, $currency),
        ])->render();

        return response()->json([
            'html' => $html,
            'count' => $items->count(),
            'keys' => $items->pluck('key')->values(),
        ]);
    }
}
