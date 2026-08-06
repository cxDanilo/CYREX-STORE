<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::withCount('pages')->orderBy('name')->get();

        return view('admin.templates.index', compact('templates'));
    }

    public function create()
    {
        $template = new Template(['default_blocks' => []]);
        $blockTypes = $this->blockTypeOptions();

        return view('admin.templates.form', compact('template', 'blockTypes'));
    }

    public function store(Request $request)
    {
        Template::create($this->validated($request));

        return redirect()->route('admin.plantillas.index')->with('status', 'Plantilla creada.');
    }

    public function edit(Template $template)
    {
        $blockTypes = $this->blockTypeOptions();

        return view('admin.templates.form', compact('template', 'blockTypes'));
    }

    public function update(Request $request, Template $template)
    {
        $template->update($this->validated($request, $template));

        return redirect()->route('admin.plantillas.index')->with('status', 'Plantilla actualizada.');
    }

    public function destroy(Template $template)
    {
        // Page.template_id tiene nullOnDelete: las páginas que usaban esta
        // plantilla no se borran ni pierden sus bloques, solo quedan sin
        // plantilla asignada.
        $template->delete();

        return back()->with('status', 'Plantilla eliminada.');
    }

    private function validated(Request $request, ?Template $template = null): array
    {
        $validTypes = array_keys(config('cms_blocks'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('templates', 'slug')->ignore($template?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'default_blocks' => ['array'],
            'default_blocks.*' => [Rule::in($validTypes)],
        ]);

        $data['default_blocks'] = array_values($data['default_blocks'] ?? []);

        return $data;
    }

    private function blockTypeOptions(): array
    {
        return collect(config('cms_blocks'))
            ->map(fn ($def, $type) => ['type' => $type, 'label' => $def['label'] ?? $type])
            ->values()
            ->all();
    }
}
