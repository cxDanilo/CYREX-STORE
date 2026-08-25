<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Support\Cart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * TTL de la caché de datos de header/footer (categorías, menú, páginas
     * en footer, redes sociales). Estos datos los edita un admin de forma
     * poco frecuente, así que un caché con vencimiento corto (sin lógica
     * de invalidación manual) es más seguro a largo plazo que un
     * "rememberForever" que dependa de que CADA controller que los toca
     * (Menu, Page, SocialLink...) recuerde invalidarlo correctamente. Un
     * cambio tarda como máximo este tiempo en reflejarse en el sitio.
     */
    private const NAV_CACHE_TTL = 300;

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
                'navCategories' => Cache::remember('nav.categories.with_children', self::NAV_CACHE_TTL, fn () => Category::parents()->with('children')->get()),
                'categoryMenuScope' => Setting::get('category_menu_scope', 'shop'),
                'whatsappNumber' => $whatsappNumber,
                'cartItems' => Cart::items(),
                'cartCount' => Cart::count(),
                'cartCurrency' => $currency,
                'cartTotal' => Cart::total($rate, $currency),
                'cartWhatsappUrl' => Cart::whatsappMessage($whatsappNumber, $rate, $currency),
                'logoHeight' => Setting::get('logo_height', '60'),
                'logoUrl' => $this->resolveLogoUrl(),
                'whatsappBtnText' => Setting::get('whatsapp_btn_text', 'Escríbenos'),
                'shopCtaText' => Setting::get('shop_cta_text', 'Ver tienda'),
                'headerMenuItems' => $this->resolveHeaderMenuItems(),
            ]);
        });

        View::composer('layouts.app', function ($view) {
            // La activa siempre tiene prioridad sobre la de teaser — nunca
            // se muestran las dos a la vez en la barra de anuncio, aunque
            // ambas existan (ej. Navidad activa + teaser de Año Nuevo).
            $activePromotion = Promotion::active();

            $view->with([
                'logoHeight' => Setting::get('logo_height', '60'),
                'logoUrl' => $this->resolveLogoUrl(),
                'footerCategories' => Cache::remember('footer.categories', self::NAV_CACHE_TTL, fn () => Category::parents()->get()),
                'footerPages' => Cache::remember('footer.pages', self::NAV_CACHE_TTL, fn () => Page::inFooter()->published()->get()),
                'whatsappNumber' => Setting::get('whatsapp_number', '59177947379'),
                'whatsappCommunityUrl' => Setting::get('whatsapp_community_url', ''),
                'whatsappCommunityBtnText' => Setting::get('whatsapp_community_btn_text', 'Únete a nuestra comunidad'),
                'footerWhatsappBtnText' => Setting::get('footer_whatsapp_btn_text', 'Escríbenos por WhatsApp'),
                'footerTagline' => Setting::get('footer_tagline', 'Componentes y periféricos gamer en Bolivia. Santa Cruz y Cochabamba.'),
                'accentColor' => Setting::get('accent_color', '#FFD900'),
                'reducedMotion' => Setting::get('reduced_motion', 'off'),
                'socialLinks' => Cache::remember('footer.social_links', self::NAV_CACHE_TTL, fn () => SocialLink::ordered()->get()),
                'ga4MeasurementId' => Setting::get('ga4_measurement_id', ''),
                'promoBarActive' => $activePromotion,
                'promoBarTeaser' => $activePromotion ? null : Promotion::inTeaser(),
                'promoModal' => $activePromotion?->show_as_modal ? $activePromotion : null,
            ]);
        });

        // Comparten la promo activa las 4 vistas que renderizan cards de
        // producto (ver Product::activePromotion()) — separado del
        // composer de arriba porque partials.shop-results se renderiza
        // solo (fragmento AJAX), sin pasar por layouts.app.
        View::composer(
            ['partials.shop-results', 'cms.blocks.productos', 'cms.blocks.categoria-rotativa', 'product'],
            fn ($view) => $view->with('cardActivePromotion', Promotion::active())
        );
    }

    private function resolveLogoUrl(): string
    {
        $path = Setting::get('logo_path');

        return $path ? Storage::disk('uploads')->url($path) : asset('images/logo-horizontal.png');
    }

    /**
     * Links del header. Si no existe todavía el menú "header_nav" (admin
     * no lo creó/editó nunca), cae a los mismos dos links hardcodeados de
     * siempre — el comportamiento no cambia hasta que alguien edite el menú.
     */
    private function resolveHeaderMenuItems()
    {
        return Cache::remember('nav.header_menu_items', self::NAV_CACHE_TTL, function () {
            $menu = Menu::where('key', 'header_nav')->with('items.page')->first();

            if (! $menu) {
                return collect([
                    ['label' => 'Inicio', 'url' => route('home')],
                    ['label' => 'Tienda', 'url' => route('shop')],
                ]);
            }

            return $menu->items
                ->reject(fn ($item) => $item->page && $item->page->status !== 'published')
                ->map(fn ($item) => [
                    'label' => $item->label,
                    'url' => $item->page ? route('page.show', $item->page->slug) : ($item->url ?: '#'),
                ])
                ->values();
        });
    }
}
