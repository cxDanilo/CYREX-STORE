<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    private const ICONS = ['i-cpu', 'i-mouse', 'i-chair'];

    public function index()
    {
        $categories = Category::whereNull('parent_id')
            ->withCount('products')
            ->with(['children' => function ($query) {
                $query->withCount('products');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parents = Category::whereNull('parent_id')->orderBy('sort_order')->get();
        $category = new Category(['sort_order' => 0]);

        return view('admin.categories.form', ['category' => $category, 'parents' => $parents, 'icons' => self::ICONS]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // Se agrega al final del grupo (misma categoría padre o principal)
        // en vez de pedirle al admin un número — el orden real se maneja
        // arrastrando las filas en el listado.
        $data['sort_order'] = Category::where('parent_id', $data['parent_id'] ?? null)->max('sort_order') + 1;

        if ($request->hasFile('icon_image')) {
            $data['icon_image'] = $this->storeIcon($request);
        }

        Category::create($data);

        return redirect()->route('admin.categorias.index')->with('status', 'Categoría creada.');
    }

    public function edit(Category $category)
    {
        $parents = Category::whereNull('parent_id')->where('id', '!=', $category->id)->orderBy('sort_order')->get();

        return view('admin.categories.form', ['category' => $category, 'parents' => $parents, 'icons' => self::ICONS]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);

        if ($request->hasFile('icon_image')) {
            $this->deleteIcon($category);
            $data['icon_image'] = $this->storeIcon($request);
        } elseif ($request->boolean('remove_icon_image')) {
            $this->deleteIcon($category);
            $data['icon_image'] = null;
        }

        $category->update($data);

        return redirect()->route('admin.categorias.index')->with('status', 'Categoría actualizada.');
    }

    public function destroy(Category $category)
    {
        $childrenCount = $category->children()->count();
        $productsCount = $category->products()->count();

        if ($childrenCount > 0) {
            return back()->with('error', "No se puede eliminar \"{$category->name}\": tiene {$childrenCount} subcategoría(s). Eliminalas o reasignalas primero.");
        }

        if ($productsCount > 0) {
            return back()->with('error', "No se puede eliminar \"{$category->name}\": tiene {$productsCount} producto(s) asociado(s). Reasignalos a otra categoría primero.");
        }

        $this->deleteIcon($category);
        $category->delete();

        return back()->with('status', 'Categoría eliminada.');
    }

    /**
     * Recibe el nuevo orden (arrastrado en el listado) de las categorías
     * de un mismo grupo — todas principales, o todas hijas de un mismo
     * padre — y reasigna sort_order según la posición en la lista.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            Category::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $hasChildren = $category && $category->exists && $category->children()->exists();

        $parentRules = ['nullable', Rule::exists('categories', 'id')->where('parent_id', null)];

        if ($category) {
            $parentRules[] = Rule::notIn([$category->id]);
        }

        $parentRules[] = function ($attribute, $value, $fail) use ($hasChildren) {
            if ($value && $hasChildren) {
                $fail('Esta categoría tiene subcategorías propias, no puede convertirse en subcategoría de otra.');
            }
        };

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($category?->id),
            ],
            'parent_id' => $parentRules,
            'icon' => ['nullable', 'in:'.implode(',', self::ICONS)],
            'icon_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
            'component_type' => ['nullable', 'in:'.implode(',', array_keys(config('pc_builder.component_types')))],
        ]);

        unset($data['icon_image']);

        if (! empty($data['parent_id'])) {
            $data['icon'] = null;
        }

        return $data;
    }

    private function storeIcon(Request $request): string
    {
        $file = $request->file('icon_image');
        $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
        $file->storeAs('categories', $filename, 'uploads');

        return 'categories/'.$filename;
    }

    private function deleteIcon(Category $category): void
    {
        if ($category->icon_image) {
            Storage::disk('uploads')->delete($category->icon_image);
        }
    }
}
