<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::orderBy('name')->get();

        // Cuántos productos usan cada marca AHORA MISMO — brand es texto
        // libre en products (no una foreign key a esta tabla), así que se
        // cuenta por nombre en vez de una relación real.
        $counts = Product::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->selectRaw('brand, count(*) as total')
            ->groupBy('brand')
            ->pluck('total', 'brand');

        return view('admin.brands.index', compact('brands', 'counts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')],
        ]);

        Brand::create($data);

        return back()->with('status', 'Marca agregada.');
    }

    /**
     * Página de marca (/marcas/{slug}) -- acá se carga todo el
     * contenido de las 10 secciones. Los productos para elegir se
     * mandan una sola vez a la vista (igual que ya hace
     * DiscountGroupController con su buscador de productos): nada de
     * esto pega a un endpoint de búsqueda, Alpine filtra en memoria.
     */
    public function edit(Brand $brand)
    {
        $productsForPicker = Product::where('status', 'active')
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name ?? 'Sin categoría',
                'price' => (float) $p->price,
                'currency' => $p->currency,
                'image_url' => $p->image_thumb_url,
            ])
            ->values();

        $categories = Category::whereNotNull('parent_id')->orderBy('name')->get();

        return view('admin.brands.edit', compact('brand', 'productsForPicker', 'categories'));
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand->id)],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('brands', 'slug')->ignore($brand->id)],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'is_page_published' => ['nullable', 'boolean'],
        ]);

        $brand->name = $validated['name'];
        $brand->slug = $validated['slug'] ?: ($brand->slug ?: Str::slug($validated['name']));
        $brand->accent_color = $validated['accent_color'] ?: null;
        $brand->is_page_published = $request->boolean('is_page_published');

        if ($request->hasFile('logo')) {
            $brand->logo = $this->storeLogo($request->file('logo'));
        }

        $brand->page_data = $this->pageDataFromRequest($request, $brand);

        $brand->save();

        return back()->with('status', 'Página de '.$brand->name.' guardada.');
    }

    public function destroy(Brand $brand)
    {
        // No toca los productos que ya tengan esta marca cargada (brand es
        // texto libre, no una foreign key) -- solo deja de aparecer como
        // opción para elegir en productos nuevos o al editar otro.
        $brand->delete();

        return back()->with('status', 'Marca eliminada. Los productos que ya la tenían cargada no cambian.');
    }

    private function storeLogo(UploadedFile $file): string
    {
        $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
        $relativePath = 'brands/'.$filename;
        $file->storeAs('brands', $filename, 'uploads');

        ImageOptimizer::process(
            Storage::disk('uploads')->path($relativePath),
            $relativePath,
            $file->getMimeType()
        );

        return $relativePath;
    }

    /**
     * Arma el JSON de page_data a partir de los inputs del form. Los
     * repeaters (finder_cards, explore_categories, comparison rows,
     * content_items, community_posts) llegan como arrays indexados
     * (finder_cards[0][title], finder_cards[1][title]...) gracias a
     * los x-for con :name="'finder_cards[' + i + '][title]'" del
     * blade -- Laravel ya los arma como array de arrays solo, no hace
     * falta reindexar nada acá salvo filtrar filas vacías.
     */
    private function pageDataFromRequest(Request $request, Brand $brand): array
    {
        $existing = $brand->page_data ?? [];

        $finderCards = collect($request->input('finder_cards', []))
            ->filter(fn ($card) => filled($card['title'] ?? null))
            ->map(fn ($card) => [
                'title' => $card['title'],
                'description' => $card['description'] ?? null,
                'cta_label' => $card['cta_label'] ?? null,
                'product_ids' => array_values(array_filter((array) ($card['product_ids'] ?? []))),
            ])
            ->values()->all();

        $exploreCategories = collect($request->input('explore_categories', []))
            ->filter(fn ($item) => filled($item['title'] ?? null))
            ->values()
            ->map(function ($item, $i) use ($request, $existing) {
                $image = $existing['explore_categories'][$i]['image'] ?? null;
                if ($request->hasFile("explore_categories.$i.image")) {
                    $image = $this->storeLogo($request->file("explore_categories.$i.image"));
                }

                return [
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'category_slug' => $item['category_slug'] ?? null,
                    'cta_label' => $item['cta_label'] ?? null,
                    'image' => $image,
                ];
            })->values()->all();

        $comparisonRows = collect($request->input('comparison_rows', []))
            ->filter(fn ($row) => filled($row['product_id'] ?? null))
            ->map(fn ($row) => [
                'product_id' => (int) $row['product_id'],
                'formato' => $row['formato'] ?? null,
                'conectividad' => $row['conectividad'] ?? null,
                'tipo_usuario' => $row['tipo_usuario'] ?? null,
                'switch' => $row['switch'] ?? null,
                'tamano' => $row['tamano'] ?? null,
                'destacado' => $row['destacado'] ?? null,
            ])
            ->values()->all();

        $contentItems = collect($request->input('content_items', []))
            ->filter(fn ($item) => filled($item['embed_url'] ?? null))
            ->map(fn ($item) => [
                'title' => $item['title'] ?? null,
                'embed_url' => $item['embed_url'],
            ])
            ->values()->all();

        $communityPosts = collect($request->input('community_posts', []))
            ->values()
            ->map(function ($post, $i) use ($request, $existing) {
                $image = $existing['community_posts'][$i]['image'] ?? null;
                if ($request->hasFile("community_posts.$i.image")) {
                    $image = $this->storeLogo($request->file("community_posts.$i.image"));
                }

                return [
                    'image' => $image,
                    'caption' => $post['caption'] ?? null,
                    'product_tags' => $post['product_tags'] ?? null,
                ];
            })
            ->filter(fn ($post) => $post['image'])
            ->values()->all();

        return [
            'hero' => [
                'subheadline' => $request->input('hero.subheadline'),
                'description' => $request->input('hero.description'),
                'product_ids' => array_values(array_filter((array) $request->input('hero.product_ids', []))),
            ],
            'editorial' => [
                'eyebrow' => $request->input('editorial.eyebrow'),
                'title' => $request->input('editorial.title'),
                'body' => $request->input('editorial.body'),
                'image_product_id' => $request->input('editorial.image_product_id') ?: null,
            ],
            'finder_cards' => $finderCards,
            'explore_categories' => $exploreCategories,
            'selection' => [
                'product_id' => $request->input('selection.product_id') ?: null,
                'tags' => array_values(array_filter(array_map('trim', explode(',', (string) $request->input('selection.tags'))))),
                'why' => $request->input('selection.why'),
                'cta_label' => $request->input('selection.cta_label'),
            ],
            'comparison' => ['rows' => $comparisonRows],
            'content_items' => $contentItems,
            'community_posts' => $communityPosts,
        ];
    }
}
