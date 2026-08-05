<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
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

        $category->delete();

        return back()->with('status', 'Categoría eliminada.');
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
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        if (! empty($data['parent_id'])) {
            $data['icon'] = null;
        }

        return $data;
    }
}
