<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Setting;
use App\Support\ReferralRouter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PcBuilderQuoteController extends Controller
{
    private const ASSEMBLY_FEE = 10.0;

    public function download(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string'],
            'items.*.id' => ['required', 'integer'],
            'ram_qty' => ['nullable', 'integer', 'in:1,2'],
            'wants_assembly' => ['nullable', 'boolean'],
        ]);

        $rate = ExchangeRate::current();
        $types = config('pc_builder.component_types');
        $ramQty = (int) ($data['ram_qty'] ?? 1);

        $products = Product::whereIn('id', collect($data['items'])->pluck('id')->unique())->get()->keyBy('id');

        $lines = collect($data['items'])->map(function ($item) use ($products, $types, $rate, $ramQty) {
            $product = $products->get($item['id']);
            if (! $product) {
                return null;
            }

            $qty = $item['type'] === 'ram' ? $ramQty : 1;
            $unitPrice = round($product->priceInUsd($rate), 2);

            return [
                'description' => $types[$item['type']] ?? $product->name,
                'name' => $product->name,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'total' => round($unitPrice * $qty, 2),
            ];
        })->filter()->values();

        if ($lines->isEmpty()) {
            abort(422, 'No hay productos válidos en la cotización.');
        }

        // El armado no es un producto del catálogo (no tiene stock ni
        // ficha propia) — se agrega como una línea más "a mano", igual
        // de simple que las de arriba, en vez de necesitar un producto
        // falso solo para que exista una fila que sumar.
        if ($request->boolean('wants_assembly')) {
            $lines->push([
                'description' => 'Servicio',
                'name' => 'Armado e instalación',
                'qty' => 1,
                'unit_price' => self::ASSEMBLY_FEE,
                'total' => self::ASSEMBLY_FEE,
            ]);
        }

        $subtotal = round($lines->sum('total'), 2);

        $quoteNumber = (int) Setting::get('next_quote_number', 14452);
        Setting::set('next_quote_number', (string) ($quoteNumber + 1));

        $logoPath = Setting::get('logo_path');
        $logoFullPath = $logoPath ? public_path('uploads/'.$logoPath) : public_path('images/logo-horizontal.png');

        $bannerColor = Setting::get('quote_banner_color', '#FFD900');

        $pdf = Pdf::loadView('pdf.pc-builder-quote', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'quoteNumber' => $quoteNumber,
            'date' => now()->format('d/m/Y'),
            'logoFullPath' => $logoFullPath,
            'whatsappNumber' => ReferralRouter::whatsappNumber(),
            'bannerText' => Setting::get('quote_banner_text', 'COTIZACIÓN'),
            'bannerColor' => $bannerColor,
            'bannerColorLight' => $this->lightenHex($bannerColor, 0.88),
        ])->setPaper('letter');

        return $pdf->download("cotizacion-{$quoteNumber}.pdf");
    }

    /**
     * Mezcla un color hex con blanco para sacar un tono pastel del mismo
     * color — se usa para teñir el encabezado de la tabla y el fondo del
     * total con el mismo color que el banner (que cambia por temporada),
     * sin necesitar un segundo campo de color en Ajustes.
     */
    private function lightenHex(string $hex, float $percent): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = (int) round($r + (255 - $r) * $percent);
        $g = (int) round($g + (255 - $g) * $percent);
        $b = (int) round($b + (255 - $b) * $percent);

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}
