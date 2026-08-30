<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeField;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeFieldController extends Controller
{
    public function index()
    {
        $configTypes = collect(config('pc_builder.component_types'))
            ->merge(config('pc_builder.extra_attribute_types', []));

        $customTypeLabels = AttributeField::whereNotNull('type_label')
            ->distinct('type_key')
            ->pluck('type_label', 'type_key');

        // config manda si por algún motivo hay colisión de key — no
        // debería pasar, store() no deja crear type_label para un
        // type_key que ya está en config.
        $allTypes = $customTypeLabels->merge($configTypes);

        $fields = AttributeField::ordered()->get()->groupBy('type_key');

        return view('admin.attribute-fields.index', compact('allTypes', 'fields'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type_key' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/'],
            'type_label' => ['nullable', 'string', 'max:100'],
            'field_key' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/'],
            'label' => ['required', 'string', 'max:255'],
            'field_type' => ['required', Rule::in(['select', 'number', 'checkboxes'])],
        ]);

        $isNewType = ! array_key_exists($data['type_key'], config('pc_builder.component_types'))
            && ! array_key_exists($data['type_key'], config('pc_builder.extra_attribute_types', []));

        if ($isNewType) {
            $alreadyNamed = AttributeField::where('type_key', $data['type_key'])
                ->whereNotNull('type_label')
                ->exists();

            if (! $alreadyNamed && empty($data['type_label'])) {
                return back()->withInput()->with('error', 'Es un tipo nuevo — completá el nombre que va a aparecer en Categorías.');
            }
        } else {
            // El tipo ya tiene su propio nombre en config — no hace falta
            // (ni conviene) guardar uno acá también.
            $data['type_label'] = null;
        }

        if (array_key_exists($data['field_key'], config("pc_builder.fields.{$data['type_key']}", []))) {
            return back()->withInput()->with('error', "\"{$data['field_key']}\" ya es un campo incorporado de este tipo — elegí otra clave.");
        }

        $exists = AttributeField::where('type_key', $data['type_key'])
            ->where('field_key', $data['field_key'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', "Ya existe un campo \"{$data['field_key']}\" en este tipo.");
        }

        $options = $this->optionsFromRequest($request);

        if (in_array($data['field_type'], ['select', 'checkboxes']) && empty($options)) {
            return back()->withInput()->with('error', 'Agregá al menos una opción para este campo.');
        }

        AttributeField::create([
            'type_key' => $data['type_key'],
            'type_label' => $data['type_label'] ?? null,
            'field_key' => $data['field_key'],
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'options' => $options ?: null,
            'shop_filter' => $request->boolean('shop_filter'),
            'sort_order' => AttributeField::where('type_key', $data['type_key'])->max('sort_order') + 1,
        ]);

        return back()->with('status', 'Campo agregado.');
    }

    /**
     * No se permite cambiar type_key/field_key/field_type acá: son la
     * "forma" del dato ya guardado en products.compat — cambiarlos a
     * mitad de camino dejaría datos viejos incompatibles con el campo
     * nuevo. Para eso hay que borrar y crear de nuevo.
     */
    public function update(Request $request, AttributeField $attributeField)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        $options = $this->optionsFromRequest($request);

        $attributeField->update([
            'label' => $data['label'],
            'shop_filter' => $request->boolean('shop_filter'),
            'options' => in_array($attributeField->field_type, ['select', 'checkboxes']) ? ($options ?: null) : null,
        ]);

        return back()->with('status', 'Campo actualizado.');
    }

    public function destroy(AttributeField $attributeField)
    {
        $attributeField->delete();

        return back()->with('status', 'Campo eliminado. Los productos que ya tenían este dato cargado lo conservan, pero no se va a mostrar ni poder editar más.');
    }

    private function optionsFromRequest(Request $request): array
    {
        $options = [];
        $keys = $request->input('option_key', []);
        $labels = $request->input('option_label', []);

        foreach ($keys as $i => $key) {
            $key = trim($key);
            $label = trim($labels[$i] ?? '');

            if ($key !== '' && $label !== '') {
                $options[$key] = $label;
            }
        }

        return $options;
    }
}
