<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSearch;
use App\Models\AnalyticsVisit;
use App\Models\Product;
use App\Support\PageLabelResolver;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

// Todo el trabajo pasa en terminate() (después de mandar la respuesta al
// navegador) para no sumarle latencia a ninguna visita real — si esto
// fallara por lo que sea, nunca debe tumbar una página pública.
class TrackVisit
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|headless|curl|wget|python-requests|go-http-client|pingdom|uptime|ahrefsbot|semrushbot|mj12bot|dotbot|petalbot|bingpreview/i';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $this->track($request, $response);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function track(Request $request, Response $response): void
    {
        if (! $request->isMethod('GET')) {
            return;
        }

        if ($response->getStatusCode() !== 200) {
            return;
        }

        if ($request->ajax()) {
            return;
        }

        if (auth()->check()) {
            return;
        }

        $userAgent = (string) $request->userAgent();
        if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent)) {
            return;
        }

        $page = PageLabelResolver::resolve($request);
        if ($page === null) {
            return;
        }

        $visit = $this->upsertVisit($request, $page['label']);

        AnalyticsPageView::create([
            'visit_id' => $visit->id,
            'url_path' => $request->path(),
            'page_label' => $page['label'],
            'product_id' => $page['productId'],
        ]);

        if ($request->routeIs('shop') && $request->filled('q')) {
            $query = trim(mb_strtolower($request->q));
            if ($query !== '') {
                AnalyticsSearch::create([
                    'visit_id' => $visit->id,
                    'query' => $query,
                    'results_count' => Product::where('status', 'active')
                        ->where('name', 'like', '%'.$query.'%')
                        ->count(),
                ]);
            }
        }

        $this->maybePrune();
    }

    private function upsertVisit(Request $request, string $label): AnalyticsVisit
    {
        $sessionId = $request->session()->getId();
        $url = $request->path();
        $now = now();

        $visit = AnalyticsVisit::where('session_id', $sessionId)->first();

        if ($visit) {
            $visit->update([
                'exit_url' => $url,
                'exit_label' => $label,
                'last_seen_at' => $now,
                'page_count' => DB::raw('page_count + 1'),
            ]);

            return $visit->refresh();
        }

        $attributes = [
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->deviceType((string) $request->userAgent()),
            'referrer_domain' => $this->referrerDomain($request),
            'entry_url' => $url,
            'entry_label' => $label,
            'exit_url' => $url,
            'exit_label' => $label,
            'page_count' => 1,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ];

        try {
            return AnalyticsVisit::create($attributes);
        } catch (QueryException $e) {
            // Dos requests casi simultáneas de una sesión recién creada
            // (session_id es UNIQUE) — la que llegó segunda solo actualiza
            // la que ya se creó, en vez de fallar.
            $visit = AnalyticsVisit::where('session_id', $sessionId)->first();
            if ($visit) {
                $visit->update([
                    'exit_url' => $url,
                    'exit_label' => $label,
                    'last_seen_at' => $now,
                    'page_count' => DB::raw('page_count + 1'),
                ]);

                return $visit->refresh();
            }

            throw $e;
        }
    }

    private function deviceType(string $userAgent): string
    {
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/mobile|android|iphone/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function referrerDomain(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! $referer) {
            return 'directo';
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (! $host || str_contains($host, $request->getHost())) {
            return 'directo';
        }

        return preg_replace('/^www\./', '', $host);
    }

    // Sin cron real en el servidor, se usa la misma idea de "lotería" que
    // ya usa Laravel para su propia limpieza de sesiones
    // (config('session.lottery')) — de vez en cuando, en vez de siempre.
    private function maybePrune(): void
    {
        [$chance, $outOf] = config('session.lottery', [2, 100]);

        if (random_int(1, $outOf) > $chance) {
            return;
        }

        $cutoff = now()->subDays(config('analytics.retention_days', 90));

        AnalyticsVisit::where('last_seen_at', '<', $cutoff)->delete();
        AnalyticsPageView::where('created_at', '<', $cutoff)->delete();
        AnalyticsSearch::where('created_at', '<', $cutoff)->delete();
    }
}
