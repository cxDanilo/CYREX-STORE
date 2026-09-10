<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\SavedBuild;
use Illuminate\Http\Request;

class SavedBuildController extends Controller
{
    // Mismo cargo fijo que PcBuilderQuoteController — si algún día se
    // hace configurable desde Ajustes, hay que actualizar los dos.
    private const ASSEMBLY_FEE = 10.0;

    public function index()
    {
        $builds = SavedBuild::where('status', 'approved')
            ->withCount('items')
            ->latest('approved_at')
            ->paginate(12);

        return view('saved-builds.index', compact('builds'));
    }

    public function show(SavedBuild $savedBuild)
    {
        $savedBuild->load('items');

        return view('saved-builds.show', ['build' => $savedBuild]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'visitor_name' => ['required', 'string', 'min:2', 'max:60'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string'],
            'items.*.id' => ['required', 'integer'],
            'ram_qty' => ['nullable', 'integer', 'in:1,2'],
            'wants_assembly' => ['nullable', 'boolean'],
        ]);

        // Igual que la cotización en PDF: nunca se confía en nombres/precios
        // que mande el navegador, todo se recalcula acá a partir de los IDs.
        $rate = ExchangeRate::current();
        $ramQty = (int) ($data['ram_qty'] ?? 1);
        $wantsAssembly = $request->boolean('wants_assembly');

        $products = Product::whereIn('id', collect($data['items'])->pluck('id')->unique())->get()->keyBy('id');

        $lines = collect($data['items'])->map(function ($item) use ($products, $ramQty, $rate) {
            $product = $products->get($item['id']);
            if (! $product) {
                return null;
            }

            return [
                'type' => $item['type'],
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image_url' => $product->image_thumb_url,
                'unit_price_usd' => round($product->priceInUsd($rate), 2),
                'qty' => $item['type'] === 'ram' ? $ramQty : 1,
                'compat' => $product->compat ?? [],
            ];
        })->filter()->values();

        if ($lines->isEmpty()) {
            abort(422, 'No hay productos válidos en este armado.');
        }

        $assemblyFee = $wantsAssembly ? self::ASSEMBLY_FEE : null;
        $partsTotal = $lines->sum(fn ($line) => $line['unit_price_usd'] * $line['qty']);

        $build = SavedBuild::create([
            'visitor_name' => trim($data['visitor_name']),
            'status' => 'pending',
            'platform' => $lines->firstWhere('type', 'cpu')['compat']['platform'] ?? null,
            'ram_qty' => $ramQty,
            'wants_assembly' => $wantsAssembly,
            'assembly_fee_usd' => $assemblyFee,
            'total_usd' => round($partsTotal + ($assemblyFee ?? 0), 2),
            'rate' => $rate,
        ]);

        $build->items()->createMany($lines->all());

        return redirect()->route('saved-builds.show', $build)->with('justPublished', true);
    }
}
