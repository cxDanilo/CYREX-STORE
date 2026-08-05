<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Support\Cart;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('partials.nav', function ($view) {
            $rate = ExchangeRate::current();
            $currency = Setting::get('default_currency', 'USD');
            $whatsappNumber = Setting::get('whatsapp_number', '59177947379');

            $view->with([
                'navCategories' => Category::parents()->with('children')->get(),
                'categoryMenuScope' => Setting::get('category_menu_scope', 'shop'),
                'cartItems' => Cart::items(),
                'cartCount' => Cart::count(),
                'cartCurrency' => $currency,
                'cartTotal' => Cart::total($rate, $currency),
                'cartWhatsappUrl' => Cart::whatsappMessage($whatsappNumber, $rate, $currency),
            ]);
        });
    }
}
