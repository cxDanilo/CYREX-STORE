<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

/**
 * Plantillas de fechas bolivianas recurrentes para el sistema de
 * promociones. Se siembran todas con active=false — Danilo las prende
 * desde Admin → Promociones cuando quiera usarlas, así no hay que
 * reprogramar nada cada año (ver Promotion::windowCandidates(), que
 * reproyecta recurring_month/recurring_day sobre el año actual).
 * Idempotente (firstOrCreate por slug).
 *
 * discount_label queda vacío a propósito en todas — son porcentajes de
 * descuento reales que definís vos al activar cada promo, no algo para
 * inventar acá.
 */
class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'slug' => 'reyes',
                'name' => 'Reyes',
                'banner_text' => 'Ofertas de Reyes — hasta el 6 de enero',
                'teaser_text' => 'Se vienen las ofertas de Reyes 👀',
                'teaser_starts_at' => '2026-12-22',
                'starts_at' => '2027-01-01',
                'ends_at' => '2027-01-06',
                'is_recurring' => true,
                'recurring_month' => 1,
                'recurring_day' => 6,
            ],
            [
                'slug' => 'dia-del-padre',
                'name' => 'Día del Padre',
                'banner_text' => 'Ofertas por el Día del Padre',
                'teaser_text' => 'Se viene algo para el Día del Padre',
                'teaser_starts_at' => '2027-03-05',
                'starts_at' => '2027-03-12',
                'ends_at' => '2027-03-19',
                'is_recurring' => true,
                'recurring_month' => 3,
                'recurring_day' => 19,
            ],
            [
                'slug' => 'dia-de-la-madre',
                'name' => 'Día de la Madre 2027',
                'banner_text' => 'Ofertas por el Día de la Madre',
                'teaser_text' => 'Algo se viene el 27 de mayo 👀',
                'teaser_starts_at' => '2027-05-13',
                'starts_at' => '2027-05-20',
                'ends_at' => '2027-05-27',
                'is_recurring' => true,
                'recurring_month' => 5,
                'recurring_day' => 27,
            ],
            [
                'slug' => 'dia-del-amor-y-la-amistad',
                'name' => 'Día del Amor y la Amistad',
                'banner_text' => 'Ofertas por el Día del Amor y la Amistad',
                'teaser_text' => 'Se viene algo para el 21 de septiembre',
                'teaser_starts_at' => '2026-09-11',
                'starts_at' => '2026-09-16',
                'ends_at' => '2026-09-21',
                'is_recurring' => true,
                'recurring_month' => 9,
                'recurring_day' => 21,
            ],
            [
                // OJO: no es una fecha fija (es "el último viernes de
                // noviembre", que se mueve todos los años) — por eso NO
                // se sembró como recurrente. Cada año hay que revisar y
                // ajustar starts_at/ends_at a mano antes de activarla, y
                // confirmar con Danilo la fecha real de "Días Amarillos"
                // (no es necesariamente la misma que el Black Friday de
                // otros países).
                'slug' => 'black-friday-dias-amarillos',
                'name' => 'Black Friday / Días Amarillos (revisar fecha cada año)',
                'banner_text' => 'Días Amarillos — ofertas por tiempo limitado',
                'teaser_text' => 'Se vienen los Días Amarillos',
                'teaser_starts_at' => '2026-11-20',
                'starts_at' => '2026-11-23',
                'ends_at' => '2026-11-27',
                'is_recurring' => false,
                'recurring_month' => null,
                'recurring_day' => null,
            ],
            [
                'slug' => 'navidad',
                'name' => 'Navidad',
                'banner_text' => 'Ofertas de Navidad',
                'teaser_text' => 'La Navidad se acerca 🎄',
                'teaser_starts_at' => '2026-12-05',
                'starts_at' => '2026-12-15',
                'ends_at' => '2026-12-25',
                'is_recurring' => true,
                'recurring_month' => 12,
                'recurring_day' => 25,
                'show_as_modal' => true,
            ],
            [
                'slug' => 'ano-nuevo',
                'name' => 'Año Nuevo',
                'banner_text' => 'Ofertas de Año Nuevo',
                'teaser_text' => 'Cerrá el año con Cyrex',
                'teaser_starts_at' => '2026-12-25',
                'starts_at' => '2026-12-29',
                'ends_at' => '2027-01-01',
                'is_recurring' => true,
                'recurring_month' => 1,
                'recurring_day' => 1,
            ],
        ];

        foreach ($promotions as $data) {
            Promotion::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge(['active' => false, 'show_as_modal' => false], $data)
            );
        }

        $this->command?->info('Promociones sembradas: '.Promotion::count().' (todas inactivas — activalas desde Admin → Promociones).');
    }
}
