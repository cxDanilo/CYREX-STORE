<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductActivityLog;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->orderByDesc('created_at');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }

        $products = $query->paginate(15)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('parent_id')->orderBy('name')->get();
        $product = new Product(['status' => 'active', 'currency' => 'USD']);
        $activityLogs = collect();

        return view('admin.products.form', compact('categories', 'product', 'activityLogs'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['specs'] = $this->specsFromRequest($request);
        $data['compat'] = $this->compatFromRequest($request, Category::find($data['category_id']));
        $variants = $this->variantsFromRequest($request);
        $data['has_variants'] = count($variants) > 0;
        $data['sold_out_at'] = $data['is_sold_out'] ? now() : null;

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request);
        }

        $product = Product::create($data);
        $this->syncVariants($product, $variants);
        $this->syncGalleryImages($product, $request);
        ProductActivityLog::record($product, 'created');

        return redirect()->route('admin.productos.index')->with('status', 'Producto creado.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('parent_id')->orderBy('name')->get();
        $product->load('variants');
        $activityLogs = ProductActivityLog::where('product_id', $product->id)->orderByDesc('created_at')->limit(20)->get();

        return view('admin.products.form', compact('product', 'categories', 'activityLogs'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->id);
        $data['specs'] = $this->specsFromRequest($request);
        $data['compat'] = $this->compatFromRequest($request, Category::find($data['category_id']));
        $variants = $this->variantsFromRequest($request);
        $data['has_variants'] = count($variants) > 0;

        // Capturado ANTES de guardar: Eloquent sincroniza sus valores
        // "originales" con los nuevos apenas el save() termina, así que
        // pedir getOriginal() DESPUÉS de update() ya devuelve el valor
        // nuevo — hay que guardarse el snapshot de antes a mano.
        $before = $product->getAttributes();

        // sold_out_at solo se toca en la transición: se fija la primera
        // vez que se marca agotado (para poder contar los 7 días desde
        // ahí) y se limpia apenas se destilda — si ya estaba marcado y
        // se vuelve a guardar sin cambiar esto, la fecha original no se
        // pisa.
        if ($data['is_sold_out'] && ! $product->is_sold_out) {
            $data['sold_out_at'] = now();
        } elseif (! $data['is_sold_out']) {
            $data['sold_out_at'] = null;
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($product);
            $data['image'] = $this->storeImage($request);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($product);
            $data['image'] = null;
        }

        $product->update($data);
        $this->logChanges($product, $before);
        $this->syncVariants($product, $variants);
        $this->syncGalleryImages($product, $request);

        return redirect()->route('admin.productos.index')->with('status', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        ProductActivityLog::record($product, 'deleted');
        $this->deleteImage($product);
        foreach ($product->images as $image) {
            Storage::disk('uploads')->delete($image->path);
        }
        $product->delete();

        return back()->with('status', 'Producto eliminado.');
    }

    private function logChanges(Product $product, array $before): void
    {
        ProductActivityLog::logFieldChanges($product, $before);
    }

    public function toggleStatus(Product $product)
    {
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('status', $product->status === 'active' ? 'Producto publicado.' : 'Producto puesto en privado.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('products', 'slug')->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:USD,BOB'],
            'sku' => ['nullable', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $data['is_sold_out'] = $request->boolean('is_sold_out');
        unset($data['image'], $data['gallery_images']);

        return $data;
    }

    private function storeImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
        $file->storeAs('products', $filename, 'uploads');

        return 'products/'.$filename;
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image) {
            Storage::disk('uploads')->delete($product->image);
        }
    }

    /**
     * Galería adicional (más allá de la imagen de portada): borra primero
     * las que el admin haya tildado para quitar, después agrega las
     * nuevas que haya subido, siempre al final del orden actual.
     */
    private function syncGalleryImages(Product $product, Request $request): void
    {
        $toRemove = $request->input('remove_gallery_images', []);

        if (! empty($toRemove)) {
            $images = $product->images()->whereIn('id', $toRemove)->get();
            foreach ($images as $image) {
                Storage::disk('uploads')->delete($image->path);
                $image->delete();
            }
        }

        $files = $request->file('gallery_images', []);

        if (empty($files)) {
            return;
        }

        $nextOrder = $product->images()->max('sort_order') + 1;

        foreach ($files as $file) {
            $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
            $file->storeAs('products', $filename, 'uploads');

            $product->images()->create([
                'path' => 'products/'.$filename,
                'sort_order' => $nextOrder++,
            ]);
        }
    }

    private function specsFromRequest(Request $request): array
    {
        $specs = [];
        $keys = $request->input('spec_key', []);
        $values = $request->input('spec_value', []);

        foreach ($keys as $i => $key) {
            $key = trim($key);
            $value = trim($values[$i] ?? '');

            if ($key !== '' && $value !== '') {
                $specs[$key] = $value;
            }
        }

        return $specs;
    }

    /**
     * Los campos de compatibilidad dependen de la categoría (un procesador
     * pide socket/plataforma/TDP, un gabinete pide form factors/largo de
     * GPU/radiador, etc.) — config/pc_builder.php define qué campos le
     * corresponden a cada tipo de componente, y acá solo leemos los que
     * apliquen según la categoría elegida. Si la categoría no está marcada
     * como pieza de PC (component_type null), no se guarda nada.
     */
    private function compatFromRequest(Request $request, ?Category $category): ?array
    {
        $type = $category?->component_type;
        $fields = $type ? config("pc_builder.fields.$type") : null;

        if (! $fields) {
            return null;
        }

        $compat = [];

        foreach ($fields as $key => $field) {
            if ($field['type'] === 'checkboxes') {
                $values = array_values($request->input("compat.$key", []));
                if (! empty($values)) {
                    $compat[$key] = $values;
                }

                continue;
            }

            $value = $request->input("compat.$key");

            if ($value === null || $value === '') {
                continue;
            }

            $compat[$key] = $field['type'] === 'number' ? (float) $value : $value;
        }

        return $compat ?: null;
    }

    private function variantsFromRequest(Request $request): array
    {
        $variants = [];

        foreach ($request->input('variants', []) as $variant) {
            if (empty($variant['variant_value'])) {
                continue;
            }

            $variants[] = [
                'id' => $variant['id'] ?? null,
                'variant_type' => $variant['variant_type'] !== '' ? $variant['variant_type'] : 'Color',
                'variant_value' => $variant['variant_value'],
                'sku' => $variant['sku'] ?? null,
                'stock' => ($variant['stock'] ?? '') !== '' ? (int) $variant['stock'] : 0,
                'price_override' => ($variant['price_override'] ?? '') !== '' ? $variant['price_override'] : null,
            ];
        }

        return $variants;
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $keptIds = [];

        foreach ($variants as $variant) {
            $id = $variant['id'];
            unset($variant['id']);

            if ($id && $product->variants()->where('id', $id)->exists()) {
                $product->variants()->where('id', $id)->update($variant);
                $keptIds[] = $id;
            } else {
                $keptIds[] = $product->variants()->create($variant)->id;
            }
        }

        $product->variants()->whereNotIn('id', $keptIds)->delete();
    }
}
