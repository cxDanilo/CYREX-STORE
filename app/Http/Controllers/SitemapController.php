<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Sitemap generado en vivo (no un archivo estático) para que siempre
     * refleje el catálogo real — se cachea 1 hora para no reconstruirlo
     * en cada visita de un crawler.
     */
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $urls = [
                ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
                ['loc' => route('shop'), 'changefreq' => 'daily', 'priority' => '0.9'],
                ['loc' => route('pc-builder'), 'changefreq' => 'weekly', 'priority' => '0.7'],
            ];

            foreach (Category::all() as $category) {
                $urls[] = [
                    'loc' => route('shop', ['category' => $category->slug]),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ];
            }

            foreach (Product::where('status', 'active')->get() as $product) {
                $urls[] = [
                    'loc' => route('product.show', $product->slug),
                    'lastmod' => $product->updated_at->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }

            foreach (Page::published()->get() as $page) {
                $urls[] = [
                    'loc' => route('page.show', $page->slug),
                    'lastmod' => $page->updated_at->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($urls as $url) {
                $xml .= "  <url>\n";
                $xml .= '    <loc>'.e($url['loc'])."</loc>\n";
                if (! empty($url['lastmod'])) {
                    $xml .= '    <lastmod>'.$url['lastmod']."</lastmod>\n";
                }
                $xml .= '    <changefreq>'.$url['changefreq']."</changefreq>\n";
                $xml .= '    <priority>'.$url['priority']."</priority>\n";
                $xml .= "  </url>\n";
            }

            $xml .= '</urlset>';

            return $xml;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
