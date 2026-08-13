<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function edit()
    {
        $currentRate = ExchangeRate::current();
        $currencyMode = Setting::get('currency_mode', 'both');
        $defaultCurrency = Setting::get('default_currency', 'USD');
        $whatsappNumber = Setting::get('whatsapp_number', '59177947379');
        $categoryMenuScope = Setting::get('category_menu_scope', 'shop');
        $logoHeight = Setting::get('logo_height', '60');
        $logoPath = Setting::get('logo_path');
        $whatsappBtnText = Setting::get('whatsapp_btn_text', 'Escríbenos');
        $shopCtaText = Setting::get('shop_cta_text', 'Ver tienda');
        $footerWhatsappBtnText = Setting::get('footer_whatsapp_btn_text', 'Escríbenos por WhatsApp');
        $footerTagline = Setting::get('footer_tagline', 'Componentes y periféricos gamer en Bolivia. Santa Cruz y Cochabamba.');
        $accentColor = Setting::get('accent_color', '#FFD900');
        $reducedMotion = Setting::get('reduced_motion', 'off');
        $ga4MeasurementId = Setting::get('ga4_measurement_id', '');
        $shopBannerImages = json_decode(Setting::get('shop_banner_images', '[]'), true) ?: [];
        $quoteBannerText = Setting::get('quote_banner_text', 'COTIZACIÓN');
        $quoteBannerColor = Setting::get('quote_banner_color', '#FFD900');
        $showExchangeRateBadge = Setting::get('show_exchange_rate_badge', 'on');
        $whatsappCommunityUrl = Setting::get('whatsapp_community_url', '');
        $whatsappCommunityBtnText = Setting::get('whatsapp_community_btn_text', 'Únete a nuestra comunidad');

        return view('admin.settings.edit', compact(
            'currentRate', 'currencyMode', 'defaultCurrency', 'whatsappNumber', 'categoryMenuScope',
            'logoHeight', 'logoPath', 'whatsappBtnText', 'shopCtaText', 'footerWhatsappBtnText',
            'footerTagline', 'accentColor', 'reducedMotion', 'ga4MeasurementId', 'shopBannerImages',
            'quoteBannerText', 'quoteBannerColor', 'showExchangeRateBadge', 'whatsappCommunityUrl',
            'whatsappCommunityBtnText'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'rate' => ['nullable', 'numeric', 'min:0.01'],
            'currency_mode' => ['required', 'in:both,usd_only,bob_only'],
            'default_currency' => ['required', 'in:USD,BOB'],
            'whatsapp_number' => ['required', 'string', 'regex:/^[0-9]{6,15}$/'],
            'category_menu_scope' => ['required', 'in:all,shop'],
            'logo_height' => ['required', 'integer', 'min:20', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'whatsapp_btn_text' => ['required', 'string', 'max:60'],
            'shop_cta_text' => ['required', 'string', 'max:60'],
            'footer_whatsapp_btn_text' => ['required', 'string', 'max:80'],
            'footer_tagline' => ['required', 'string', 'max:200'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'reduced_motion' => ['required', 'in:on,off'],
            'ga4_measurement_id' => ['nullable', 'string', 'regex:/^G-[A-Za-z0-9]+$/'],
            'remove_banner_images' => ['nullable', 'array'],
            'remove_banner_images.*' => ['string'],
            'new_banner_images' => ['nullable', 'array'],
            'new_banner_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'quote_banner_text' => ['required', 'string', 'max:60'],
            'quote_banner_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'show_exchange_rate_badge' => ['required', 'in:on,off'],
            'whatsapp_community_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_community_btn_text' => ['required', 'string', 'max:60'],
        ]);

        if ($request->filled('rate') && (float) $data['rate'] !== ExchangeRate::current()) {
            ExchangeRate::create(['rate' => $data['rate']]);
        }

        Setting::set('currency_mode', $data['currency_mode']);
        Setting::set('default_currency', $data['default_currency']);
        Setting::set('whatsapp_number', $data['whatsapp_number']);
        Setting::set('category_menu_scope', $data['category_menu_scope']);
        Setting::set('logo_height', (string) $data['logo_height']);
        Setting::set('whatsapp_btn_text', $data['whatsapp_btn_text']);
        Setting::set('shop_cta_text', $data['shop_cta_text']);
        Setting::set('footer_whatsapp_btn_text', $data['footer_whatsapp_btn_text']);
        Setting::set('footer_tagline', $data['footer_tagline']);
        Setting::set('accent_color', $data['accent_color']);
        Setting::set('reduced_motion', $data['reduced_motion']);
        Setting::set('ga4_measurement_id', $data['ga4_measurement_id'] ?? '');
        Setting::set('quote_banner_text', $data['quote_banner_text']);
        Setting::set('quote_banner_color', $data['quote_banner_color']);
        Setting::set('show_exchange_rate_badge', $data['show_exchange_rate_badge']);
        Setting::set('whatsapp_community_url', $data['whatsapp_community_url'] ?? '');
        Setting::set('whatsapp_community_btn_text', $data['whatsapp_community_btn_text']);
        $this->updateBannerImages($request);

        if ($request->hasFile('logo')) {
            $this->deleteLogo();
            $file = $request->file('logo');
            $filename = 'branding/'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $file->storeAs('', $filename, 'uploads');
            Setting::set('logo_path', $filename);
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo();
            Setting::set('logo_path', null);
        }

        return back()->with('status', 'Ajustes guardados.');
    }

    /**
     * El banner de Tienda es una lista simple de imágenes (guardadas
     * como JSON en Settings, no una tabla propia) — el admin puede
     * quitar cualquiera de las que ya están y/o subir nuevas en el
     * mismo guardado. ShopController elige una al azar en cada visita.
     */
    private function updateBannerImages(Request $request): void
    {
        $current = json_decode(Setting::get('shop_banner_images', '[]'), true) ?: [];
        $toRemove = $request->input('remove_banner_images', []);
        $kept = array_values(array_diff($current, $toRemove));

        foreach ($toRemove as $path) {
            Storage::disk('uploads')->delete($path);
        }

        if ($request->hasFile('new_banner_images')) {
            foreach ($request->file('new_banner_images') as $file) {
                $filename = 'banners/'.Str::random(20).'.'.$file->getClientOriginalExtension();
                $file->storeAs('', $filename, 'uploads');
                $kept[] = $filename;
            }
        }

        Setting::set('shop_banner_images', json_encode($kept));
    }

    private function deleteLogo(): void
    {
        $current = Setting::get('logo_path');

        if ($current) {
            Storage::disk('uploads')->delete($current);
        }
    }
}
