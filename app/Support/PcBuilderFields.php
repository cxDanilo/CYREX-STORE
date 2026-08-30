<?php

namespace App\Support;

use App\Models\AttributeField;
use App\Models\PcBuilderOption;
use App\Models\ShopFilterOverride;

/**
 * Expande config('pc_builder.fields'): cualquier campo con
 * 'options' => 'dynamic:<grupo>' se reemplaza por las opciones reales
 * cargadas en pc_builder_options (Admin → Compatibilidad), en vez de un
 * array fijo en el código. El resto de la definición (label, type) sigue
 * viniendo del config, que es donde vive la estructura de cada tipo de
 * pieza — solo las LISTAS de valores son editables desde el admin.
 *
 * Además mezcla los campos cargados en Admin → Atributos personalizados
 * (tabla attribute_fields): o suman un campo a un tipo que ya existe acá
 * (ej. "panel_mallado" en "case"), o dan de alta un type_key totalmente
 * nuevo que no está en config/pc_builder.php.
 *
 * Y aplica shop_filter_overrides: permite prender/apagar el filtro de
 * tienda de un campo YA INCORPORADO (ej. Almacenamiento -> Tipo) desde
 * el admin, sin tocar el shop_filter fijo de config/pc_builder.php.
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

        foreach (ShopFilterOverride::all() as $override) {
            if (isset($fields[$override->type_key][$override->field_key])) {
                $fields[$override->type_key][$override->field_key]['shop_filter'] = $override->enabled;
            }
        }

        foreach (AttributeField::ordered()->get() as $attributeField) {
            $fields[$attributeField->type_key][$attributeField->field_key] = [
                'label' => $attributeField->label,
                'type' => $attributeField->field_type,
                'options' => $attributeField->options ?? [],
                'shop_filter' => $attributeField->shop_filter,
            ];
        }

        return $fields;
    }
}
