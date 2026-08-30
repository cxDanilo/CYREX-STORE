<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeField;
use App\Models\PcBuilderOption;
use App\Models\ShopFilterOverride;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeFieldController extends Controller
{
    /**
     * Una sola pantalla con dos pestañas: "Compatibilidad" (valores de
     * PcBuilderOption, ej. sockets) y "Atributos personalizados" (campos
     * enteros nuevos, esta tabla). Antes eran dos pantallas separadas y
     * generaba confusión no saber cuál usar para cada cosa.
     */
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

        $compatGroups = PcBuilderOptionController::GROUPS;
        $compatOptions = PcBuilderOption::orderBy('sort_order')->orderBy('id')->get()->groupBy('group');

        // Ya incluye lo que haya en shop_filter_overrides — así la casilla
        // de "campos ya incorporados" muestra el estado real, no el fijo
        // de config/pc_builder.php.
        $resolvedFields = \App\Support\PcBuilderFields::resolved();

        return view('admin.attribute-fields.index', compact('allTypes', 'fields', 'compatGroups', 'compatOptions', 'resolvedFields'));
    }

    /**
     * Prende/apaga el filtro de tienda de un campo YA INCORPORADO (ej.
     * Almacenamiento -> Tipo) sin tocar config/pc_builder.php. Solo
     * select — checkboxes guarda un array y number no tiene opciones,
     * ninguno de los dos sirve como filtro de tienda tal cual está armado
     * ShopController::resolveShopFilter().
     */
    public function toggleBuiltInFilter(Request $request)
    {
        $data = $request->validate([
            'type_key' => ['required', 'string'],
            'field_key' => ['required', 'string'],
        ]);

        $field = config("pc_builder.fields.{$data['type_key']}.{$data['field_key']}");

        if (! $field || ($field['type'] ?? null) !== 'select') {
            abort(404);
        }

        ShopFilterOverride::updateOrCreate(
            ['type_key' => $data['type_key'], 'field_key' => $data['field_key']],
            ['enabled' => $request->boolean('enabled')]
        );

        return back()->with('status', 'Filtro actualizado.');
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

        if ($this->labelExists($data['type_key'], $data['label'])) {
            return back()->withInput()->with('error', "Ya existe un campo llamado \"{$data['label']}\" en este tipo — no hace falta crear otro, ya está ahí arriba.");
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

        if ($this->labelExists($attributeField->type_key, $data['label'], $attributeField->id)) {
            return back()->withInput()->with('error', "Ya existe un campo llamado \"{$data['label']}\" en este tipo.");
        }

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

    /**
     * El chequeo de arriba (field_key) solo evita repetir la misma clave
     * interna. Esto evita el caso más real: alguien crea un campo con
     * otra clave pero el mismo nombre (ej. "Plataforma" de nuevo con
     * field_key distinto) — sin esto quedaría duplicado y confuso en el
     * formulario de productos, aunque técnicamente no choque nada.
     */
    private function labelExists(string $typeKey, string $label, ?int $excludeId = null): bool
    {
        $normalized = mb_strtolower(trim($label));

        foreach (config("pc_builder.fields.$typeKey", []) as $field) {
            if (mb_strtolower(trim($field['label'])) === $normalized) {
                return true;
            }
        }

        return AttributeField::where('type_key', $typeKey)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get()
            ->contains(fn ($f) => mb_strtolower(trim($f->label)) === $normalized);
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
