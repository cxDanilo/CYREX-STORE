<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with('template')->orderByDesc('created_at')->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        $page = new Page(['status' => 'draft']);
        $templates = Template::orderBy('name')->get();

        return view('admin.pages.form', compact('page', 'templates'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $page = DB::transaction(function () use ($data) {
            $page = Page::create($data + [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if ($page->template_id) {
                $this->applyTemplateBlocks($page);
            }

            return $page;
        });

        return redirect()
            ->route('admin.paginas.content', $page)
            ->with('status', 'Página creada'.($page->template_id ? ' con la estructura de la plantilla.' : '.').' Completá el contenido acá.');
    }

    public function edit(Page $page)
    {
        $templates = Template::orderBy('name')->get();

        return view('admin.pages.form', compact('page', 'templates'));
    }

    public function update(Request $request, Page $page)
    {
        // Cambiar de plantilla acá solo actualiza la referencia — nunca
        // vuelve a generar bloques ni toca el contenido ya cargado, para
        // no pisar edición existente.
        $page->update($this->validated($request, $page) + ['updated_by' => auth()->id()]);

        return redirect()->route('admin.paginas.index')->with('status', 'Página actualizada.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return back()->with('status', 'Página eliminada.');
    }

    public function content(Page $page)
    {
        return view('admin.pages.editor', compact('page'));
    }

    private function applyTemplateBlocks(Page $page): void
    {
        $template = Template::find($page->template_id);

        foreach (($template->default_blocks ?? []) as $i => $type) {
            $page->blocks()->create([
                'type' => $type,
                'data' => [],
                'sort_order' => $i,
            ]);
        }
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('pages', 'slug')->ignore($page?->id),
                Rule::notIn(['admin', 'tienda', 'producto', 'carrito', 'buscar-sugerencias', '404']),
            ],
            'template_id' => ['nullable', 'exists:templates,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status' => ['required', 'in:draft,published'],
            'show_in_footer' => ['nullable', 'boolean'],
            'footer_sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['show_in_footer'] = $request->boolean('show_in_footer');

        return $data;
    }
}
