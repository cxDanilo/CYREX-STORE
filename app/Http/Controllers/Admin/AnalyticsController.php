<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSearch;
use App\Models\AnalyticsVisit;
use App\Support\UserAgentParser;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    // Ventana fija para todos los rankings de abajo (páginas principales,
    // salidas, búsquedas) — no hace falta un selector de fechas para la
    // primera versión de esto.
    private const WINDOW_DAYS = 30;

    public function index()
    {
        $windowStart = now()->subDays(self::WINDOW_DAYS);

        return view('admin.analytics.index', [
            'onlineCount' => $this->onlineCount(),
            'summary' => $this->summary(),
            'trend' => $this->dailyTrend(),
            'topPages' => AnalyticsPageView::where('created_at', '>=', $windowStart)
                ->selectRaw('page_label, COUNT(*) as total')
                ->groupBy('page_label')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'exitPages' => AnalyticsVisit::where('last_seen_at', '>=', $windowStart)
                ->selectRaw('exit_label, COUNT(*) as total')
                ->groupBy('exit_label')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'topSearches' => AnalyticsSearch::where('created_at', '>=', $windowStart)
                ->selectRaw('query, COUNT(*) as total, MAX(created_at) as last_searched_at')
                ->groupBy('query')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'engagement' => $this->engagement($windowStart),
            'devices' => $this->deviceBreakdown($windowStart),
            'browsers' => $this->userAgentBreakdown($windowStart, fn ($ua) => UserAgentParser::browser($ua)),
            'systems' => $this->userAgentBreakdown($windowStart, fn ($ua) => UserAgentParser::os($ua)),
            'referrers' => AnalyticsVisit::where('first_seen_at', '>=', $windowStart)
                ->selectRaw('referrer_domain, COUNT(*) as total')
                ->groupBy('referrer_domain')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
        ]);
    }

    public function online()
    {
        $query = AnalyticsVisit::where('last_seen_at', '>=', now()->subMinutes(5));

        $count = (clone $query)->count();
        $recent = (clone $query)->orderByDesc('last_seen_at')->limit(20)->get(['exit_label', 'last_seen_at']);

        return response()->json([
            'count' => $count,
            'visitors' => $recent->map(fn ($v) => [
                'page' => $v->exit_label,
                'hace' => $v->last_seen_at->diffForHumans(),
            ]),
        ]);
    }

    private function onlineCount(): int
    {
        return AnalyticsVisit::where('last_seen_at', '>=', now()->subMinutes(5))->count();
    }

    private function summary(): array
    {
        $periods = [
            'hoy' => [now()->startOfDay(), now()],
            'ayer' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7d' => [now()->subDays(7), now()],
            '28d' => [now()->subDays(28), now()],
        ];

        $result = [];
        foreach ($periods as $key => [$start, $end]) {
            $result[$key] = [
                'visitas' => AnalyticsVisit::whereBetween('first_seen_at', [$start, $end])->count(),
                'vistas' => AnalyticsPageView::whereBetween('created_at', [$start, $end])->count(),
            ];
        }

        return $result;
    }

    private function dailyTrend(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = AnalyticsVisit::where('first_seen_at', '>=', $start)
            ->selectRaw('DATE(first_seen_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $days[] = [
                'label' => $date->translatedFormat('d/M'),
                'total' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $days;
    }

    private function engagement(Carbon $windowStart): array
    {
        $visits = AnalyticsVisit::where('last_seen_at', '>=', $windowStart)
            ->get(['page_count', 'first_seen_at', 'last_seen_at']);

        $total = $visits->count();
        if ($total === 0) {
            return ['avgSeconds' => 0, 'bounceRate' => 0, 'sampleSize' => 0];
        }

        $bounces = $visits->where('page_count', 1)->count();
        $engaged = $visits->where('page_count', '>', 1);

        $avgSeconds = $engaged->isEmpty()
            ? 0
            : (int) round($engaged->avg(fn ($v) => $v->last_seen_at->diffInSeconds($v->first_seen_at)));

        return [
            'avgSeconds' => $avgSeconds,
            'bounceRate' => round(($bounces / $total) * 100),
            'sampleSize' => $engaged->count(),
        ];
    }

    private function deviceBreakdown(Carbon $windowStart): array
    {
        $labels = ['desktop' => 'PC', 'mobile' => 'Celular', 'tablet' => 'Tablet'];

        return AnalyticsVisit::where('first_seen_at', '>=', $windowStart)
            ->selectRaw('device_type, COUNT(*) as total')
            ->groupBy('device_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $labels[$row->device_type] ?? 'Otro', 'total' => $row->total])
            ->all();
    }

    // Navegador y SO no tienen su propia columna — se derivan del
    // user_agent ya guardado, así que se agrupan acá en PHP en vez de en
    // SQL (el volumen de esta tienda no justifica una columna calculada
    // aparte, y evita otra migración).
    private function userAgentBreakdown(Carbon $windowStart, callable $classify): array
    {
        return AnalyticsVisit::where('first_seen_at', '>=', $windowStart)
            ->pluck('user_agent')
            ->map(fn ($ua) => $classify((string) $ua))
            ->countBy()
            ->sortDesc()
            ->take(6)
            ->map(fn ($total, $label) => ['label' => $label, 'total' => $total])
            ->values()
            ->all();
    }
}
