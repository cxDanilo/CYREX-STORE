<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $mouse = Category::where('slug', 'mouse')->first();
        $procesadores = Category::where('slug', 'procesadores')->first();
        $monitores = Category::where('slug', 'monitores')->first();
        $teclados = Category::where('slug', 'teclados')->first();

        // Producto CON variantes (Color)
        $aj179 = Product::create([
            'category_id' => $mouse->id,
            'name' => 'AJAZZ AJ179 PRO',
            'slug' => 'aj179-pro',
            'description' => 'Mouse gamer tri-modo (USB / 2.4G-8K / Bluetooth), sensor PAW3395 hasta 26,000 DPI.',
            'price' => 47.00,
            'currency' => 'USD',
            'sku' => 'AJZ-179PRO',
            'stock' => 10,
            'has_variants' => true,
            'status' => 'active',
            'specs' => json_encode([
                'Sensor' => 'PAW3395, hasta 26,000 DPI',
                'Conectividad' => 'USB-C · 2.4GHz 8K · Bluetooth 5.1',
                'Switches' => 'Ópticos, 80M clics',
                'Peso' => '58g',
            ]),
        ]);

        ProductVariant::create([
            'product_id' => $aj179->id,
            'variant_type' => 'Color',
            'variant_value' => 'Blanco',
            'sku' => 'AJZ-179PRO-WHT',
            'stock' => 2,
        ]);

        ProductVariant::create([
            'product_id' => $aj179->id,
            'variant_type' => 'Color',
            'variant_value' => 'Negro',
            'sku' => 'AJZ-179PRO-BLK',
            'stock' => 8,
        ]);

        // Productos sin variantes
        Product::create([
            'category_id' => $procesadores->id,
            'name' => 'Ryzen 7 5700 8-core',
            'slug' => 'ryzen-7-5700',
            'description' => 'Procesador AMD Ryzen 7 5700, 8 núcleos / 16 hilos.',
            'price' => 189.00,
            'currency' => 'USD',
            'sku' => 'AMD-R7-5700',
            'stock' => 3,
            'status' => 'active',
        ]);

        Product::create([
            'category_id' => $monitores->id,
            'name' => 'AOC AGON 27" 165Hz QHD',
            'slug' => 'aoc-agon-27-165hz',
            'description' => 'Monitor gaming curvo 27", QHD, 165Hz, FreeSync Premium.',
            'price' => 2380.00,
            'currency' => 'BOB',
            'sku' => 'AOC-AGON27',
            'stock' => 1,
            'status' => 'active',
        ]);

        Product::create([
            'category_id' => $teclados->id,
            'name' => 'EPOMAKER AK820 PRO',
            'slug' => 'epomaker-ak820-pro',
            'description' => 'Teclado mecánico hot-swap, conexión Bluetooth/2.4G/USB-C.',
            'price' => 980.00,
            'currency' => 'BOB',
            'sku' => 'EPO-AK820PRO',
            'stock' => 6,
            'status' => 'active',
        ]);
    }
}