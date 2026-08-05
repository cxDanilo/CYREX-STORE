<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $currentRate = ExchangeRate::current();
        $currencyMode = Setting::get('currency_mode', 'both');
        $defaultCurrency = Setting::get('default_currency', 'USD');
        $whatsappNumber = Setting::get('whatsapp_number', '59177947379');
        $categoryMenuScope = Setting::get('category_menu_scope', 'shop');

        return view('admin.settings.edit', compact('currentRate', 'currencyMode', 'defaultCurrency', 'whatsappNumber', 'categoryMenuScope'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'rate' => ['nullable', 'numeric', 'min:0.01'],
            'currency_mode' => ['required', 'in:both,usd_only,bob_only'],
            'default_currency' => ['required', 'in:USD,BOB'],
            'whatsapp_number' => ['required', 'string', 'regex:/^[0-9]{6,15}$/'],
            'category_menu_scope' => ['required', 'in:all,shop'],
        ]);

        if ($request->filled('rate') && (float) $data['rate'] !== ExchangeRate::current()) {
            ExchangeRate::create(['rate' => $data['rate']]);
        }

        Setting::set('currency_mode', $data['currency_mode']);
        Setting::set('default_currency', $data['default_currency']);
        Setting::set('whatsapp_number', $data['whatsapp_number']);
        Setting::set('category_menu_scope', $data['category_menu_scope']);

        return back()->with('status', 'Ajustes guardados.');
    }
}
