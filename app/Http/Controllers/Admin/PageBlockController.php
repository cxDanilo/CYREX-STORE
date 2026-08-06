<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Support\PageRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Contrato JSON genérico para leer/escribir el contenido (bloques) de una
 * página. No sabe nada de GrapesJS ni de ningún editor en particular — es
 * lo que hace posible reemplazar el editor visual sin tocar el backend.
 */
class PageBlockController extends Controller
{
    public function index(Page $page)
    {
        return response()->json([
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
            ],
            'blocks' => $page->blocks()->orderBy('sort_order')->get(['id', 'type', 'data', 'sort_order']),
            'types' => collect(config('cms_blocks'))->map(fn ($def, $type) => [
                'type' => $type,
                'label' => $def['label'] ?? $type,
                'category' => $def['category'] ?? 'Otros',
                'icon' => $def['icon'] ?? '',
                'defaults' => $def['defaults'] ?? [],
                'fields' => $this->resolveFieldOptions($def['fields'] ?? []),
            ])->values(),
        ]);
    }

    /**
     * Resuelve fuentes de opciones dinámicas declaradas en config/cms_blocks.php
     * (hoy solo 'categories', el listado real de categorías padre). Mantiene el
     * config estático/cacheable y evita que un campo 'select' quede hardcodeado
     * con datos que cambian (ej. si se crea una categoría nueva desde el admin).
     */
    private function resolveFieldOptions(array $fields): array
    {
        foreach ($fields as $key => $field) {
            if (($field['options'] ?? null) === 'categories') {
                $fields[$key]['options'] = Category::parents()
                    ->get(['slug', 'name'])
                    ->map(fn ($cat) => ['id' => $cat->slug, 'name' => $cat->name])
                    ->values();
            }

            if (($field['type'] ?? null) === 'repeater' && ! empty($field['fields'])) {
                $fields[$key]['fields'] = $this->resolveFieldOptions($field['fields']);
            }
        }

        return $fields;
    }

    public function store(Request $request, Page $page)
    {
        $validTypes = array_keys(config('cms_blocks'));

        $data = $request->validate([
            'blocks' => ['array'],
            'blocks.*.id' => ['nullable', 'integer'],
            'blocks.*.type' => ['required', 'string', Rule::in($validTypes)],
            'blocks.*.data' => ['array'],
            'blocks.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $blocks = $data['blocks'] ?? [];

        DB::transaction(function () use ($page, $blocks) {
            // Cargado una sola vez para poder comparar "¿esto realmente
            // cambió?" sin una query por bloque, y para no escribir una fila
            // que llega idéntica a como ya está (el editor autoguarda cada
            // ~1.2s mientras se edita — sin esto, cada bloque sin tocar
            // igual se reescribiría, moviendo su updated_at sin motivo).
            $existing = $page->blocks()->get()->keyBy('id');
            $keptIds = [];

            foreach ($blocks as $block) {
                $payload = [
                    'type' => $block['type'],
                    'data' => $block['data'] ?? [],
                    'sort_order' => $block['sort_order'],
                ];

                $id = $block['id'] ?? null;
                $current = $id ? $existing->get($id) : null;

                if ($current) {
                    $keptIds[] = $current->id;

                    $changed = $current->type !== $payload['type']
                        || $current->sort_order !== $payload['sort_order']
                        || $current->data !== $payload['data'];

                    if ($changed) {
                        $current->update($payload);
                    }
                } else {
                    $keptIds[] = $page->blocks()->create($payload)->id;
                }
            }

            $page->blocks()->whereNotIn('id', $keptIds)->delete();
        });

        return response()->json([
            'blocks' => $page->blocks()->orderBy('sort_order')->get(['id', 'type', 'data', 'sort_order']),
        ]);
    }

    public function preview(Request $request, Page $page)
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'data' => ['array'],
        ]);

        return response()->json([
            'html' => PageRenderer::renderBlockData($data['type'], $data['data'] ?? []),
        ]);
    }
}
