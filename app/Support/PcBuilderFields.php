<?php

namespace App\Support;

use App\Models\PcBuilderOption;

/**
 * Expande config('pc_builder.fields'): cualquier campo con
 * 'options' => 'dynamic:<grupo>' se reemplaza por las opciones reales
 * cargadas en pc_builder_options (Admin → Compatibilidad), en vez de un
 * array fijo en el código. El resto de la definición (label, type) sigue
 * viniendo del config, que es donde vive la estructura de cada tipo de
 * pieza — solo las LISTAS de valores son editables desde el admin.
 */
class PcBuilderFields
{
    public static function resolved(): array
    {
        $fields = config('pc_builder.fields');

        foreach ($fields as $type => $typeFields) {
            foreach ($typeFields as $key => $field) {
                if (is_string($field['options'] ?? null) && str_starts_with($field['options'], 'dynamic:')) {
                    $group = substr($field['options'], strlen('dynamic:'));
                    $fields[$type][$key]['options'] = PcBuilderOption::optionsFor($group);
                }
            }
        }

        return $fields;
    }
}
