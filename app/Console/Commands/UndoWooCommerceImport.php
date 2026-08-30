<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UndoWooCommerceImport extends Command
{
    protected $signature = 'woocommerce:undo-import {--force : Borra de verdad. Sin este flag solo muestra qué se borraría.}';

    protected $description = 'Borra los productos que fueron creados por el importador de WooCommerce (identificados por su registro en el historial)';

    public function handle(): int
    {
        $productIds = ProductActivityLog::where('action', 'created')
            ->get()
            ->filter(fn (ProductActivityLog $log) => ($log->changes['origen']['despues'] ?? null) === 'Importado de WooCommerce')
            ->pluck('product_id')
            ->filter()
            ->unique();

        $products = Product::whereIn('id', $productIds)->get();

        if ($products->isEmpty()) {
            $this->info('No hay productos importados por WooCommerce para borrar.');

            return self::SUCCESS;
        }

        $this->info("Se encontraron {$products->count()} productos creados por el importador:");
        foreach ($products as $product) {
            $this->line('- ['.($product->sku ?: 'sin SKU').'] '.$product->name);
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Esto fue un dry-run, no se borró nada. Vuelve a correr el comando con --force para borrarlos de verdad.');

            return self::SUCCESS;
        }

        foreach ($products as $product) {
            if ($product->image) {
                Storage::disk('uploads')->delete($product->image);
            }

            $product->variants()->delete();
            ProductActivityLog::where('product_id', $product->id)->delete();
            $product->delete();
        }

        $this->info("Listo, se borraron {$products->count()} productos.");
        $this->comment('Nota: las categorías que el importador haya creado de paso no se tocan — si quedaron vacías, bórralas a mano desde el admin si quieres.');

        return self::SUCCESS;
    }
}
