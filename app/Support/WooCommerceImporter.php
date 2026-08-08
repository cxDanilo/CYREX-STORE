<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductActivityLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importa el CSV de exportación estándar de WooCommerce (Productos ->
 * Exportar, desde el propio WooCommerce). No usa la API de WooCommerce
 * ni necesita credenciales — un CSV es lo que cualquier dueño de tienda
 * ya sabe generar, y evita depender de que el sitio de origen siga
 * activo o accesible después de la migración.
 *
 * Reglas de matching, pensadas para poder correr el mismo CSV más de
 * una vez sin duplicar nada:
 * - Si el SKU de la fila ya existe en un producto local, se actualiza
 *   ese producto en vez de crear uno nuevo.
 * - La categoría se busca por nombre (sin importar mayúsculas); si no
 *   existe, se crea como categoría principal nueva.
 * - Si la imagen no se puede descargar (URL caída, timeout, etc.) el
 *   producto igual se crea/actualiza sin imagen — un error de imagen
 *   nunca debe tirar abajo la fila entera.
 */
class WooCommerceImporter
{
    /**
     * WooCommerce exporta con los encabezados en el idioma del sitio de
     * origen — acá se soportan inglés y español (confirmado contra un
     * export real en español) comparando texto ya sin acentos ni
     * signos de interrogación, para no depender de que coincida
     * carácter por carácter.
     */
    private const COLUMN_MAP = [
        'sku' => 'sku',
        'name' => 'name',
        'nombre' => 'name',
        'regular price' => 'price',
        'precio normal' => 'price',
        'sale price' => 'sale_price',
        'precio rebajado' => 'sale_price',
        'stock' => 'stock',
        'inventario' => 'stock',
        'categories' => 'categories',
        'categorias' => 'categories',
        'images' => 'images',
        'imagenes' => 'images',
        'description' => 'description',
        'descripcion' => 'description',
        'short description' => 'short_description',
        'descripcion corta' => 'short_description',
        'published' => 'published',
        'publicado' => 'published',
    ];

    /**
     * @return array{created:int, updated:int, errors:array<int, string>, warnings:array<int, string>}
     */
    public function import(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');

        if (! $handle) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['No se pudo abrir el archivo.'], 'warnings' => []];
        }

        $headerRow = fgetcsv($handle);

        if (! $headerRow) {
            fclose($handle);

            return ['created' => 0, 'updated' => 0, 'errors' => ['El archivo está vacío o no tiene encabezados.'], 'warnings' => []];
        }

        $columns = $this->mapColumns($headerRow);
        $created = 0;
        $updated = 0;
        $errors = [];
        $warnings = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            try {
                $result = $this->importRow($columns, $row);

                if ($result === null) {
                    continue;
                }

                if ($result['price_missing']) {
                    $warnings[] = "Fila {$rowNumber}: \"{$result['name']}\" se importó con precio $0 — no tenía ni \"Precio normal\" ni \"Precio rebajado\" en el CSV, revisalo a mano.";
                }

                $result['action'] === 'created' ? $created++ : $updated++;
            } catch (\Throwable $e) {
                $errors[] = "Fila {$rowNumber}: ".$e->getMessage();
            }
        }

        fclose($handle);

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Detecta en qué posición del CSV está cada columna que nos
     * interesa, comparando encabezados normalizados (minúsculas, sin
     * espacios de sobra) — así funciona sea cual sea el orden real de
     * columnas del export.
     *
     * @return array<string, int>
     */
    private function mapColumns(array $headerRow): array
    {
        $columns = [];

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader($header);

            if (isset(self::COLUMN_MAP[$normalized])) {
                $columns[self::COLUMN_MAP[$normalized]] = $index;
            }
        }

        return $columns;
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = strtr($normalized, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        $normalized = str_replace(['¿', '?'], '', $normalized);

        return trim($normalized);
    }

    private function value(array $columns, array $row, string $key): ?string
    {
        if (! isset($columns[$key]) || ! isset($row[$columns[$key]])) {
            return null;
        }

        $value = trim($row[$columns[$key]]);

        return $value === '' ? null : $value;
    }

    /**
     * @return array{action:string, name:string, price_missing:bool}|null
     */
    private function importRow(array $columns, array $row): ?array
    {
        $name = $this->value($columns, $row, 'name');

        if (! $name) {
            return null; // fila sin nombre (ej. una fila de variación WooCommerce sin producto padre) — se salta
        }

        $sku = $this->value($columns, $row, 'sku');
        $product = $sku ? Product::where('sku', $sku)->first() : null;
        $isNew = ! $product;
        $product ??= new Product();

        $before = $product->exists ? $product->getOriginal() : [];

        // Algunos catálogos de WooCommerce solo cargan "Precio rebajado" (oferta)
        // y dejan "Precio normal" vacío — si el normal no está, usamos el rebajado
        // en vez de importar el producto en $0.
        $priceValue = $this->value($columns, $row, 'price') ?? $this->value($columns, $row, 'sale_price');
        $priceMissing = $priceValue === null;

        $product->name = $name;
        $product->slug = $product->slug ?: Str::slug($name).'-'.Str::random(4);
        $product->sku = $sku;
        $product->description = $this->cleanHtml($this->value($columns, $row, 'description') ?? $this->value($columns, $row, 'short_description'));
        $product->price = (float) ($priceValue ?? 0);
        $product->currency = $product->currency ?: 'USD';
        $product->stock = (int) ($this->value($columns, $row, 'stock') ?? 0);
        $product->status = ($this->value($columns, $row, 'published') === '0') ? 'inactive' : 'active';
        $product->category_id = $this->resolveCategory($this->value($columns, $row, 'categories'));
        $product->has_variants = $product->has_variants ?? false;

        if ($isNew) {
            $imageUrl = $this->firstImageUrl($this->value($columns, $row, 'images'));

            if ($imageUrl) {
                $product->image = $this->downloadImage($imageUrl);
            }
        }

        $product->save();

        if ($isNew) {
            ProductActivityLog::record($product, 'created', ['origen' => ['antes' => null, 'despues' => 'Importado de WooCommerce']]);
        } else {
            $changes = [];
            foreach ($product->getChanges() as $key => $newValue) {
                if ($key === 'updated_at') {
                    continue;
                }
                $changes[$key] = ['antes' => $before[$key] ?? null, 'despues' => $newValue];
            }
            if (! empty($changes)) {
                ProductActivityLog::record($product, 'updated', $changes);
            }
        }

        return ['action' => $isNew ? 'created' : 'updated', 'name' => $name, 'price_missing' => $priceMissing];
    }

    /**
     * WooCommerce exporta el HTML de la descripción con los saltos de línea
     * reales convertidos a la secuencia literal de dos caracteres "\n" (para
     * que el campo no rompa el CSV) — si no se revierte, esos "\n" quedan
     * como texto visible en vez de como espacio entre bloques.
     */
    private function cleanHtml(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace(['\r\n', '\n'], "\n", $value);
    }

    private function resolveCategory(?string $categoriesField): ?int
    {
        if (! $categoriesField) {
            return null;
        }

        // WooCommerce separa categorías múltiples con comas; nos
        // quedamos con la primera, que suele ser la más específica.
        $firstCategoryName = trim(explode(',', $categoriesField)[0]);

        if ($firstCategoryName === '') {
            return null;
        }

        $category = Category::whereRaw('LOWER(name) = ?', [strtolower($firstCategoryName)])->first();

        if (! $category) {
            $category = Category::create([
                'name' => $firstCategoryName,
                'slug' => Str::slug($firstCategoryName),
                'sort_order' => 0,
            ]);
        }

        return $category->id;
    }

    private function firstImageUrl(?string $imagesField): ?string
    {
        if (! $imagesField) {
            return null;
        }

        $first = trim(explode(',', $imagesField)[0]);

        return filter_var($first, FILTER_VALIDATE_URL) ? $first : null;
    }

    private function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(8)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'products/'.Str::random(20).'.'.$extension;

            Storage::disk('uploads')->put($filename, $response->body());

            return $filename;
        } catch (\Throwable) {
            return null;
        }
    }
}
