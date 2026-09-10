<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiscountGroup;
use App\Models\Product;
use App\Models\ProductActivityLog;
use App\Models\ProductImage;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->orderByDesc('created_at');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }

        $products = $query->paginate(15)->withQueryString();
        $activeDiscountGroup = DiscountGroup::first();

        return view('admin.products.index', compact('products', 'activeDiscountGroup'));
    }

    public function create()
    {
        $categories = $this->categoriesForForm();
        $product = new Product(['status' => 'active', 'currency' => 'USD']);
        $activityLogs = collect();
        $backUrl = null;

        return view('admin.products.form', compact('categories', 'product', 'activityLogs', 'backUrl'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $category = Category::find($data['category_id']);
        $data['specs'] = $this->specsFromRequest($request);
        $data['compat'] = $this->compatFromRequest($request, $category);
        $variants = $this->variantsFromRequest($request);
        $data['has_variants'] = count($variants) > 0;
        $data['sold_out_at'] = $data['is_sold_out'] ? now() : null;

        $this->throwIfAny(
            $this->compatRequirementErrors($request, $category),
            $this->imageRequirementErrors($request, $variants, null),
        );

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request);
        }

        $product = Product::create($data);
        $this->syncVariants($product, $variants);
        $this->syncGalleryImages($product, $request);
        $product->categories()->sync($request->input('category_ids', []));
        ProductActivityLog::record($product, 'created');

        return redirect()->route('admin.productos.index')->with('status', 'Producto creado.');
    }

    public function edit(Request $request, Product $product)
    {
        $categories = $this->categoriesForForm();
        $product->load('variants', 'categories');
        $activityLogs = ProductActivityLog::where('product_id', $product->id)->orderByDesc('created_at')->limit(20)->get();
        $backUrl = $this->sanitizeBackUrl($request->query('back'));

        return view('admin.products.form', compact('product', 'categories', 'activityLogs', 'backUrl'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->id);
        $category = Category::find($data['category_id']);
        $data['specs'] = $this->specsFromRequest($request);
        $data['compat'] = $this->compatFromRequest($request, $category);
        $variants = $this->variantsFromRequest($request);
        $data['has_variants'] = count($variants) > 0;

        $this->throwIfAny(
            $this->compatRequirementErrors($request, $category),
            $this->imageRequirementErrors($request, $variants, $product),
        );

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
        $product->categories()->sync($request->input('category_ids', []));

        $backUrl = $this->sanitizeBackUrl($request->input('back'));

        return redirect($backUrl ?? route('admin.productos.index'))->with('status', 'Producto actualizado.');
    }

    /**
     * Categorías para los selectores del formulario, cada padre seguido
     * de sus propios hijos (antes se pedían todas ordenadas solo por
     * parent_id/nombre, lo que agrupaba TODOS los padres primero y
     * TODOS los hijos de TODAS las categorías después, mezclados entre
     * sí sin importar de qué padre era cada uno).
     */
    private function categoriesForForm()
    {
        return Category::whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->get()
            ->flatMap(fn ($parent) => collect([$parent])->concat($parent->children));
    }

    /**
     * Solo deja volver a la página pública del producto de este mismo
     * sitio (nunca a una URL externa) — es lo único para lo que se usa
     * "back": cuando se entra a editar desde el botón "Editar producto"
     * de la ficha pública, guardar o cancelar debería volver ahí, no
     * siempre al listado del admin.
     */
    private function sanitizeBackUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parsed = parse_url($url);

        if (! $parsed || ($parsed['host'] ?? null) !== request()->getHost() || ! str_starts_with($parsed['path'] ?? '', '/producto/')) {
            return null;
        }

        return $url;
    }

    public function destroy(Product $product)
    {
        ProductActivityLog::record($product, 'deleted');
        $this->deleteImage($product);
        foreach ($product->images as $image) {
            Storage::disk('uploads')->delete($image->path);
        }
        foreach ($product->variants as $variant) {
            if ($variant->image) {
                Storage::disk('uploads')->delete($variant->image);
            }
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

    // Agotado + oferta de un solo producto sin abrir el formulario
    // completo — pensado para cuando son solo unos pocos productos (si
    // son cientos, ver Admin\DiscountGroupController). Activar la
    // oferta acá suma el producto a la campaña activa si ya hay una
    // (misma fecha para todos); si no hay ninguna, crea una campaña de
    // un solo producto con la fecha que se indique acá, así igual
    // queda cubierta por discount-groups:expire y no hay que acordarse
    // de apagarla a mano.
    public function quickEdit(Request $request, Product $product)
    {
        $data = $request->validate([
            'is_sold_out' => ['nullable', 'boolean'],
            'offer_selected' => ['nullable', 'boolean'],
            'offer_price' => ['nullable', 'numeric', 'min:0.01', 'required_if:offer_selected,1'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $before = $product->getAttributes();

        $isSoldOut = $request->boolean('is_sold_out');
        $updates = ['is_sold_out' => $isSoldOut];

        // Misma transición que usa el formulario completo (ver update()
        // arriba): sold_out_at solo se toca cuando el estado cambia.
        if ($isSoldOut && ! $product->is_sold_out) {
            $updates['sold_out_at'] = now();
        } elseif (! $isSoldOut) {
            $updates['sold_out_at'] = null;
        }

        $product->update($updates);

        \App\Support\ProductOfferManager::apply(
            $product,
            $request->boolean('offer_selected'),
            $data['offer_price'] ?? null,
            $data['ends_at'] ?? null
        );

        ProductActivityLog::logFieldChanges($product, $before);

        return back()->with('status', 'Producto actualizado.');
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
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
        ]);

        $data['is_sold_out'] = $request->boolean('is_sold_out');
        unset($data['image'], $data['gallery_images'], $data['category_ids']);

        return $data;
    }

    private function storeImage(Request $request): string
    {
        return $this->storeUploadedImage($request->file('image'));
    }

    private function storeUploadedImage(\Illuminate\Http\UploadedFile $file): string
    {
        $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
        $relativePath = 'products/'.$filename;
        $file->storeAs('products', $filename, 'uploads');

        // Mismo optimizador que ya usa la Biblioteca de medios
        // (MediaController) — redimensiona a 2000px máx y recomprime a
        // calidad 82 sobre el propio archivo recién subido. Antes las
        // fotos de producto se guardaban tal cual las mandaba el admin,
        // a veces varios MB sin ninguna compresión.
        ImageOptimizer::process(
            Storage::disk('uploads')->path($relativePath),
            $relativePath,
            $file->getMimeType()
        );

        return $relativePath;
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
        $fields = $type ? (\App\Support\PcBuilderFields::resolved()[$type] ?? null) : null;

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

    /**
     * Cuando la categoría elegida tiene campos propios (pieza de PC o
     * solo atributo de filtro, da igual — config/pc_builder.php define
     * ambos con el mismo formato), TODOS esos campos pasan a ser
     * obligatorios. Se valida por separado de $this->validated()
     * porque los campos son dinámicos según la categoría, no se pueden
     * declarar de antemano con la sintaxis normal de reglas.
     */
    private function compatRequirementErrors(Request $request, ?Category $category): array
    {
        $type = $category?->component_type;
        $fields = $type ? (\App\Support\PcBuilderFields::resolved()[$type] ?? null) : null;

        if (! $fields) {
            return [];
        }

        $errors = [];

        foreach ($fields as $key => $field) {
            if ($field['type'] === 'checkboxes') {
                if (empty($request->input("compat.$key", []))) {
                    $errors["compat.$key"] = "El campo \"{$field['label']}\" es obligatorio.";
                }

                continue;
            }

            $value = $request->input("compat.$key");

            if ($value === null || $value === '') {
                $errors["compat.$key"] = "El campo \"{$field['label']}\" es obligatorio.";
            }
        }

        return $errors;
    }

    /**
     * La imagen principal es obligatoria — salvo que el producto ya
     * quede con al menos una variante con foto propia (ver el ajuste
     * de getImageUrlAttribute() en Product, que usa esa foto como
     * respaldo en toda la tienda cuando no hay imagen principal). Se
     * revisa DESPUÉS de armar $variants (variantsFromRequest) para
     * poder ver si alguna trae imagen nueva, sin duplicar esa lógica.
     */
    private function imageRequirementErrors(Request $request, array $variants, ?Product $existingProduct): array
    {
        if ($request->hasFile('image')) {
            return [];
        }

        if ($existingProduct?->image && ! $request->boolean('remove_image')) {
            return [];
        }

        $existingVariantImages = $existingProduct
            ? $existingProduct->variants()->whereNotNull('image')->pluck('image', 'id')
            : collect();

        $hasVariantImage = collect($variants)->contains(function ($variant) use ($existingVariantImages) {
            if ($variant['image_file']) {
                return true;
            }
            if ($variant['remove_image']) {
                return false;
            }

            return $variant['id'] && $existingVariantImages->has($variant['id']);
        });

        return $hasVariantImage ? [] : [
            'image' => 'Subí una imagen principal, o agregale foto a al menos una variante.',
        ];
    }

    // Junta los errores de todas las validaciones "extra" (las que no
    // se pueden expresar como reglas fijas de $this->validated()) en
    // una sola tirada — así el admin ve TODO lo que falta de una vez
    // en vez de corregir un grupo, reenviar, y recién ahí enterarse
    // del siguiente.
    private function throwIfAny(array ...$errorSets): void
    {
        $errors = array_merge(...$errorSets);

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function variantsFromRequest(Request $request): array
    {
        $variants = [];

        // El índice $i tiene que ser el del array de request tal cual
        // (no un contador aparte) porque más abajo se usa exactamente
        // ese mismo índice para buscar el archivo "variants.$i.image"
        // — si se contara aparte, saltear una fila vacía desalinearía
        // texto e imagen.
        foreach ($request->input('variants', []) as $i => $variant) {
            if (empty($variant['variant_value'])) {
                continue;
            }

            $variants[] = [
                'id' => $variant['id'] ?? null,
                'variant_type' => $variant['variant_type'] !== '' ? $variant['variant_type'] : 'Color',
                'variant_value' => $variant['variant_value'],
                'sku' => $variant['sku'] ?? null,
                'price_override' => ($variant['price_override'] ?? '') !== '' ? $variant['price_override'] : null,
                'is_sold_out' => $request->boolean("variants.$i.is_sold_out"),
                'image_file' => $request->file("variants.$i.image"),
                'remove_image' => $request->boolean("variants.$i.remove_image"),
            ];
        }

        return $variants;
    }

    /**
     * A diferencia de los demás campos (que solo se pisan con lo que
     * venga del form), la imagen de cada variante necesita el modelo
     * existente ANTES de actualizar — para saber si hay que borrar un
     * archivo viejo del disco al reemplazarla o sacarla.
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $keptIds = [];

        foreach ($variants as $variant) {
            $id = $variant['id'];
            unset($variant['id']);

            $imageFile = $variant['image_file'];
            $removeImage = $variant['remove_image'];
            unset($variant['image_file'], $variant['remove_image']);

            $existing = $id ? $product->variants()->where('id', $id)->first() : null;

            if ($imageFile) {
                if ($existing?->image) {
                    Storage::disk('uploads')->delete($existing->image);
                }
                $variant['image'] = $this->storeUploadedImage($imageFile);
            } elseif ($removeImage) {
                if ($existing?->image) {
                    Storage::disk('uploads')->delete($existing->image);
                }
                $variant['image'] = null;
            }

            if ($existing) {
                $existing->update($variant);
                $keptIds[] = $existing->id;
            } else {
                $keptIds[] = $product->variants()->create($variant)->id;
            }
        }

        $removed = $product->variants()->whereNotIn('id', $keptIds)->get();
        foreach ($removed as $variant) {
            if ($variant->image) {
                Storage::disk('uploads')->delete($variant->image);
            }
        }
        $product->variants()->whereNotIn('id', $keptIds)->delete();
    }
}
