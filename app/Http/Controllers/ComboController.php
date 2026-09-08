<?php

namespace App\Http\Controllers;

use App\Models\Combo;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Support\AdminCurrencyPref;

class ComboController extends Controller
{
    public function show(string $slug)
    {
        $rate = ExchangeRate::current();
        $combo = Combo::where('slug', $slug)->with('products.category')->firstOrFail();

        if (! $combo->active && ! (auth()->check() && auth()->user()->isAdmin())) {
            abort(404);
        }

        $currencyMode = Setting::get('currency_mode', 'both');
        $defaultCurrency = Setting::get('default_currency', 'USD');
        $forceBob = AdminCurrencyPref::forceBob();

        return view('combo', compact('combo', 'rate', 'currencyMode', 'defaultCurrency', 'forceBob'));
    }
}
