<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PcBuilderOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PcBuilderOptionController extends Controller
{
    /**
     * Grupos fijos — corresponden 1 a 1 con los 'dynamic:<grupo>' de
     * config/pc_builder.php. No son editables desde acá (agregar un grupo
     * nuevo requiere además cablear un campo nuevo en el config y, si hace
     * falta comparar compatibilidad, en el motor de reglas del armador),
     * pero los VALORES dentro de cada grupo sí son 100% libres.
     */
    private const GROUPS = [
        'socket' => 'Sockets (procesador / placa madre / enfriamiento)',
        'ram_type' => 'Tipos de memoria RAM',
        'form_factor' => 'Form factors (placa madre / gabinete)',
        'radiator_size' => 'Tamaños de radiador',
        'storage_type' => 'Tipos de almacenamiento',
        'gpu_tier' => 'Rendimiento aproximado de tarjeta gráfica',
        'psu_certification' => 'Certificación 80 Plus (fuentes de poder)',
        'gpu_brand' => 'Marca de tarjeta gráfica',
        'cooler_type' => 'Tipo de enfriamiento',
        'switch_type' => 'Tipo de switch (teclados)',
    ];

    public function index()
    {
        $options = PcBuilderOption::orderBy('sort_order')->orderBy('id')->get()->groupBy('group');
        $groups = self::GROUPS;

        return view('admin.pc-builder-options.index', compact('options', 'groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group' => ['required', Rule::in(array_keys(self::GROUPS))],
            'value' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $data['value'] = trim($data['value']);

        $exists = PcBuilderOption::where('group', $data['group'])->where('value', $data['value'])->exists();

        if ($exists) {
            return back()->with('error', "Ya existe la opción \"{$data['value']}\" en ese grupo.");
        }

        $data['sort_order'] = PcBuilderOption::where('group', $data['group'])->max('sort_order') + 1;

        PcBuilderOption::create($data);

        return back()->with('status', 'Opción agregada.');
    }

    public function update(Request $request, PcBuilderOption $pcBuilderOption)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        $pcBuilderOption->update($data);

        return back()->with('status', 'Opción actualizada.');
    }

    public function destroy(PcBuilderOption $pcBuilderOption)
    {
        $pcBuilderOption->delete();

        return back()->with('status', 'Opción eliminada. Los productos que ya la tenían cargada la conservan, pero no va a aparecer más como opción para elegir.');
    }
}
