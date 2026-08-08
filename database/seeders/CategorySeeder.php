<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Componentes' => [
                'icon' => 'i-cpu',
                'children' => [
                    'Procesadores' => 'cpu',
                    'Placas madre' => 'motherboard',
                    'Memorias RAM' => 'ram',
                    'Almacenamiento' => null,
                    'Fuentes de poder' => 'psu',
                    'Gabinetes' => 'case',
                    'Refrigeración' => 'cooler',
                    'Tarjetas gráficas' => 'gpu',
                ],
            ],
            'Periféricos' => [
                'icon' => 'i-mouse',
                'children' => [
                    'Mouse', 'Teclados', 'Auriculares', 'Micrófonos',
                    'Monitores', 'Cámaras', 'Parlantes', 'Mandos',
                    'Mouse pads', 'Keycaps y switches',
                ],
            ],
            'Mobiliario y accesorios' => [
                'icon' => 'i-chair',
                'children' => [
                    'Sillas gamer', 'Cables y adaptadores',
                    'Estabilizadores / UPS', 'Licencias',
                ],
            ],
        ];

        $order = 0;
        foreach ($data as $parentName => $info) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => \Str::slug($parentName),
                'icon' => $info['icon'],
                'sort_order' => $order++,
            ]);

            $childOrder = 0;
            foreach ($info['children'] as $childKey => $childValue) {
                // Algunos grupos vienen como ['Nombre' => 'component_type'],
                // otros (sin piezas de PC de por medio) como lista simple.
                [$childName, $componentType] = is_string($childKey)
                    ? [$childKey, $childValue]
                    : [$childValue, null];

                Category::create([
                    'name' => $childName,
                    'slug' => \Str::slug($childName),
                    'parent_id' => $parent->id,
                    'component_type' => $componentType,
                    'sort_order' => $childOrder++,
                ]);
            }
        }
    }
}
