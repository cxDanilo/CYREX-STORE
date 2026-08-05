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
                    'Procesadores', 'Placas madre', 'Memorias RAM',
                    'Almacenamiento', 'Fuentes de poder', 'Gabinetes', 'Refrigeración',
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
            foreach ($info['children'] as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => \Str::slug($childName),
                    'parent_id' => $parent->id,
                    'sort_order' => $childOrder++,
                ]);
            }
        }
    }
}