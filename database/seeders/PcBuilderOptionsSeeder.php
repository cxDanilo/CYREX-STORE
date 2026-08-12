<?php

namespace Database\Seeders;

use App\Models\PcBuilderOption;
use Illuminate\Database\Seeder;

/**
 * Carga en pc_builder_options los mismos valores que antes estaban
 * hardcodeados en config/pc_builder.php, para que los productos ya
 * cargados sigan funcionando igual. A partir de acá, agregar un socket
 * nuevo (ej. LGA1851 para Core Ultra) o un tipo de RAM nuevo (DDR6) se
 * hace desde Admin → Compatibilidad, no editando código. Idempotente:
 * se puede correr más de una vez sin duplicar (firstOrCreate por
 * group+value).
 */
class PcBuilderOptionsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'socket' => [
                'AM4' => 'AM4',
                'AM5' => 'AM5',
                'LGA1700' => 'LGA1700',
                'LGA1200' => 'LGA1200',
            ],
            'ram_type' => [
                'DDR4' => 'DDR4',
                'DDR5' => 'DDR5',
            ],
            'form_factor' => [
                'ATX' => 'ATX',
                'mATX' => 'mATX',
                'ITX' => 'ITX',
            ],
            'radiator_size' => [
                '0' => 'No soporta / no aplica',
                '120' => '120mm',
                '240' => '240mm',
                '280' => '280mm',
                '360' => '360mm',
            ],
            'storage_type' => [
                'SSD NVMe' => 'SSD NVMe',
                'SSD SATA' => 'SSD SATA',
                'HDD' => 'HDD',
            ],
            'gpu_tier' => [
                'entrada' => 'Gama de entrada — 1080p, ajustes bajos/medios',
                'media' => 'Gama media — 1080p/1440p, ajustes altos',
                'alta' => 'Gama alta — 1440p/4K, ajustes ultra',
            ],
            'gpu_brand' => [
                'Nvidia' => 'Nvidia',
                'AMD' => 'AMD',
                'Intel' => 'Intel Arc',
            ],
            'psu_certification' => [
                'ninguna' => 'Sin certificación (genérica)',
                '80plus' => '80 Plus (blanco)',
                '80plus_bronze' => '80 Plus Bronze',
                '80plus_silver' => '80 Plus Silver',
                '80plus_gold' => '80 Plus Gold',
                '80plus_platinum' => '80 Plus Platinum',
                '80plus_titanium' => '80 Plus Titanium',
            ],
            'cooler_type' => [
                'aire' => 'Aire',
                'liquido' => 'Líquido',
                'stock' => 'Stock',
            ],
            // Mismas claves (mecanico/membrana) que antes estaban
            // hardcodeadas en config/pc_builder.php — así los teclados
            // ya cargados con ese valor lo siguen mostrando bien.
            'switch_type' => [
                'mecanico' => 'Mecánico',
                'membrana' => 'Membrana',
            ],
        ];

        foreach ($groups as $group => $options) {
            foreach (array_values($options) as $i => $label) {
                $value = array_keys($options)[$i];

                PcBuilderOption::firstOrCreate(
                    ['group' => $group, 'value' => $value],
                    ['label' => $label, 'sort_order' => $i]
                );
            }
        }

        $this->command?->info('Opciones de Arma tu PC cargadas: '.PcBuilderOption::count().' en total.');
    }
}
