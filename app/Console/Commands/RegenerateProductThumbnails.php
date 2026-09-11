<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegenerateProductThumbnails extends Command
{
    protected $signature = 'products:regenerate-thumbnails';

    protected $description = 'Reprocesa la foto de cada producto para regenerar su miniatura en WebP (ver ImageOptimizer) — pensado para correr una sola vez sobre productos ya subidos antes de este cambio, la subida de fotos nuevas ya la genera bien sola.';

    public function handle(): int
    {
        $products = Product::whereNotNull('image')->get();
        $done = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $absolutePath = Storage::disk('uploads')->path($product->image);
                $mimeType = Storage::disk('uploads')->mimeType($product->image);

                ImageOptimizer::process($absolutePath, $product->image, $mimeType);
                $done++;
            } catch (\Throwable $e) {
                // Un archivo puntual roto/faltante no puede tirar abajo el
                // resto del catálogo — se sigue con el próximo y se avisa
                // al final cuántos quedaron sin poder reprocesarse.
                $failed++;
                $this->warn("No se pudo reprocesar el producto #{$product->id} ({$product->name}): {$e->getMessage()}");
            }
        }

        $this->info("{$done} miniatura(s) regenerada(s) en WebP".($failed ? ", {$failed} fallida(s)" : '').'.');

        return self::SUCCESS;
    }
}
