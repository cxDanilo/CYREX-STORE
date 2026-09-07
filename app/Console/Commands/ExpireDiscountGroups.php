<?php

namespace App\Console\Commands;

use App\Models\DiscountGroup;
use Illuminate\Console\Command;

class ExpireDiscountGroups extends Command
{
    protected $signature = 'discount-groups:expire';

    protected $description = 'Termina las campañas de descuento vencidas y devuelve sus productos al precio normal';

    public function handle(): int
    {
        $groups = DiscountGroup::where('ends_at', '<=', now())->get();
        $productCount = 0;

        foreach ($groups as $group) {
            $productCount += $group->revertProducts()->count();
        }

        $this->info("{$groups->count()} campaña(s) vencida(s), {$productCount} producto(s) vuelto(s) a su precio normal.");

        return self::SUCCESS;
    }
}
