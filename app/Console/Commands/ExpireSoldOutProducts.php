<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductActivityLog;
use Illuminate\Console\Command;

class ExpireSoldOutProducts extends Command
{
    protected $signature = 'products:expire-sold-out';

    protected $description = 'Pasa a Privado los productos que llevan 7+ días marcados como Agotado';

    public function handle(): int
    {
        $products = Product::where('is_sold_out', true)
            ->where('status', 'active')
            ->where('sold_out_at', '<=', now()->subDays(7))
            ->get();

        foreach ($products as $product) {
            $before = $product->getAttributes();
            $product->update(['status' => 'inactive']);
            ProductActivityLog::logFieldChanges($product, $before);
        }

        $this->info("{$products->count()} producto(s) pasado(s) a Privado por llevar más de 7 días agotados.");

        return self::SUCCESS;
    }
}
