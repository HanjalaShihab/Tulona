<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateConversion;
use App\Models\Click;
use App\Models\Comparison;
use App\Models\LandingPage;
use App\Models\Offer;
use App\Models\PageView;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Privacy-friendly admin analytics (§ visitor/engagement terms).
 *
 * The platform has no user accounts, so "users" terminology is never used and
 * no personal data is exposed — only aggregate, hashed-click signals.
 *
 * Only metrics with real backing data are rendered (affiliate clicks, click
 * sources, click devices, import conversions). Visitor/impression-style metrics
 * that are not tracked yet (unique visitors, sessions, page views, product
 * views, CTR, search queries, geography, real-time) always render an
 * "awaiting tracking" empty state — never fabricated figures.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);

        return view('admin.analytics.overview', array_merge(
            $this->shared($p),
            [
                'kpis' => $this->kpiCards($clicks, $p),
                'trend' => $this->dailyTrend($clicks, $p),
                'pageTrend' => $this->pageViewTrend($p),
                'realtime' => $this->realtime($request),
                'topProducts' => $this->topProducts($clicks, 6),
                'merchantShare' => $this->merchantShare($clicks, $p),
                'topComparisons' => $this->comparisonClicks($clicks, 6),
                'referrerMix' => $this->referrerMix($clicks),
                'funnel' => $this->funnel($clicks, $p),
                'opportunities' => $this->opportunities($clicks),
                'conversions' => $this->conversions($p),
            ]
        ));
    }

    public function visitors(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);

        return view('admin.analytics.visitors', array_merge(
            $this->shared($p),
            [
                'visitorStats' => $this->visitorStats($p),
                'pageTrend' => $this->pageViewTrend($p),
                'realtime' => $this->realtime($request),
                'clickerStats' => $this->clickerStats($clicks, $p),
                'trend' => $this->dailyTrend($clicks, $p),
                'referrerMix' => $this->referrerMix($clicks),
            ]
        ));
    }

    public function products(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);
        $clickCounts = $this->productClickCounts($clicks);

        return view('admin.analytics.products', array_merge(
            $this->shared($p),
            [
                'products' => $this->productPerformance($clickCounts, $request),
                'sort' => $this->sortSpec($request),
            ]
        ));
    }

    public function clicks(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);

        return view('admin.analytics.clicks', array_merge(
            $this->shared($p),
            [
                'kpis' => $this->kpiCards($clicks, $p),
                'trend' => $this->dailyTrend($clicks, $p),
                'merchantShare' => $this->merchantShare($clicks, $p),
                'conversions' => $this->conversions($p),
            ]
        ));
    }

    public function search(Request $request): View
    {
        return view('admin.analytics.search', $this->shared($this->period($request)));
    }

    public function comparisons(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);

        return view('admin.analytics.comparisons', array_merge(
            $this->shared($p),
            [
                'comparisonRows' => $this->comparisonClicks($clicks, 100),
                'clickCount' => (clone $clicks)->count(),
            ]
        ));
    }

    public function categories(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);
        $total = (clone $clicks)->count();

        $rows = $clicks->clone()
            ->join('products', 'products.id', '=', 'clicks.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(*) as clicks'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('clicks')
            ->get();

        $productCounts = Product::query()
            ->selectRaw('category_id, COUNT(*) as n')
            ->whereIn('category_id', $rows->pluck('id'))
            ->groupBy('category_id')
            ->pluck('n', 'category_id');

        $rows = $rows->map(fn ($r) => (object) [
            'id' => $r->id,
            'name' => $r->name,
            'products' => (int) ($productCounts[$r->id] ?? 0),
            'clicks' => (int) $r->clicks,
            'share' => $total > 0 ? round($r->clicks / $total * 100, 1) : 0.0,
        ]);

        return view('admin.analytics.categories', array_merge(
            $this->shared($p),
            ['categoryRows' => $rows, 'clickCount' => $total]
        ));
    }

    public function sources(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);

        return view('admin.analytics.sources', array_merge(
            $this->shared($p),
            ['referrerMix' => $this->referrerMix($clicks)]
        ));
    }

    public function devices(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);
        $total = (clone $clicks)->count();

        $byDevice = $clicks->clone()
            ->selectRaw('COALESCE(user_agent_family, "unknown") as device, COUNT(*) as c')
            ->groupBy('device')->get();

        $rows = $byDevice->map(fn ($r) => (object) [
            'name' => $r->device === 'unknown' ? 'Unknown' : ucfirst($r->device),
            'device' => $r->device === 'unknown' ? 'Unknown' : ucfirst($r->device),
            'clicks' => (int) $r->c,
            'share' => $total > 0 ? round($r->c / $total * 100, 1) : 0.0,
        ]);

        return view('admin.analytics.devices', array_merge(
            $this->shared($p),
            ['deviceRows' => $rows, 'clickCount' => $total]
        ));
    }

    public function landingPages(Request $request): View
    {
        $p = $this->period($request);
        $clicks = $this->clicksQuery($p);
        $total = (clone $clicks)->count();

        $landingClicks = $clicks->clone()
            ->where('referrer_page', 'like', '/landing/%')
            ->selectRaw('SUBSTR(referrer_page, 10) as slug, COUNT(*) as c')
            ->groupBy('slug')->pluck('c', 'slug');

        $rows = LandingPage::query()
            ->withCount([])
            ->get(['id', 'title', 'slug', 'status'])
            ->map(fn ($lp) => (object) [
                'id' => $lp->id,
                'title' => $lp->title,
                'slug' => $lp->slug,
                'status' => $lp->status,
                'clicks' => (int) ($landingClicks[$lp->slug] ?? 0),
            ])
            ->sortByDesc('clicks')
            ->values();

        return view('admin.analytics.landing-pages', array_merge(
            $this->shared($p),
            ['landingRows' => $rows, 'clickCount' => $total]
        ));
    }

    // ── Shared view scaffolding ────────────────────────────────────────────────

    /** @return array{period: array, section: string} */
    protected function shared(array $p): array
    {
        return ['period' => $p, 'section' => request()->route()->getName()];
    }

    /**
     * Period resolution: ?period=today|7d|30d|90d|1y|custom and optional
     * from/to dates. Always returns an inclusive bounded window plus the
     * previous equal-length window for deltas.
     */
    protected function period(Request $request): array
    {
        $key = (string) $request->query('period', '30d');
        if (! in_array($key, ['today', '7d', '90d', '1y', 'custom'], true)) {
            $key = '30d';
        }

        $from = $request->query('from');
        $to = $request->query('to');
        $today = today();

        $start = match ($key) {
            'today' => $today->copy(),
            '7d' => $today->copy()->subDays(6),
            '90d' => $today->copy()->subDays(89),
            '1y' => $today->copy()->subYear()->addDay(),
            'custom' => $this->parseDate($from) ?? $today->copy()->subDays(29),
            default => $today->copy()->subDays(29),
        };

        $end = $this->parseDate($to) ?? $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $length = intdiv($end->copy()->endOfDay()->timestamp - $start->copy()->startOfDay()->timestamp, 86400) + 1;

        return [
            'key' => $key,
            'label' => $key === 'custom'
                ? $start->format('M j, Y').' – '.$end->format('M j, Y')
                : $this->periodLabel($key, $start, $end),
            'start' => $start->copy()->startOfDay(),
            'end' => $end->copy()->endOfDay(),
            'prevStart' => $start->copy()->startOfDay()->subDays($length),
            'prevEnd' => $start->copy()->startOfDay()->subDay(),
            'numDays' => $length,
        ];
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function periodLabel(string $key, Carbon $start, Carbon $end): string
    {
        return match ($key) {
            'today' => 'Today',
            '7d' => 'Last 7 days',
            '90d' => 'Last 90 days',
            '1y' => 'Last year',
            default => $start->format('M j').' – '.$end->format('M j, Y'),
        };
    }

    protected function clicksQuery(array $p): Builder
    {
        return Click::query()
            ->whereBetween('clicked_at', [$p['start'], $p['end']]);
    }

    // ── Page-view / visitor metrics (anonymous beacon data) ───────────────────

    protected function pageViewQuery(array $p, ?string $pathPattern = null): Builder
    {
        return PageView::query()
            ->whereBetween('viewed_at', [$p['start'], $p['end']])
            ->when($pathPattern, fn ($q) => $q->where('path', 'like', $pathPattern));
    }

    protected function countPageViews(array $p, ?string $pathPattern = null): int
    {
        return (int) $this->pageViewQuery($p, $pathPattern)->count();
    }

    protected function uniqueVisitors(array $p): int
    {
        return (int) $this->pageViewQuery($p)
            ->whereNotNull('ip_hash')
            ->distinct('ip_hash')
            ->count('ip_hash');
    }

    protected function pageViewTrend(array $p): Collection
    {
        $byDay = $this->pageViewQuery($p)
            ->selectRaw('date(viewed_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $visitorsByDay = $this->pageViewQuery($p)
            ->whereNotNull('ip_hash')
            ->selectRaw('date(viewed_at) as d, COUNT(DISTINCT ip_hash) as v')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $series = collect();
        for ($d = $p['start']->copy(); $d->lessThanOrEqualTo($p['end']); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $series->push((object) [
                'day' => $key,
                'label' => $d->format('M j'),
                'views' => (int) ($byDay[$key]->c ?? 0),
                'visitors' => (int) ($visitorsByDay[$key]->v ?? 0),
            ]);
        }

        return $series;
    }

    /** Honest visitor KPI set: real values backed by the beacon; gaps stay null. */
    protected function visitorStats(array $p): Collection
    {
        return collect([
            (object) ['label' => 'Unique visitors', 'icon' => '👤', 'value' => $this->uniqueVisitors($p), 'real' => true,
                'note' => 'Distinct hashed IPs on public pages'],
            (object) ['label' => 'Page views', 'icon' => '📄', 'value' => $this->countPageViews($p), 'real' => true,
                'note' => 'Views recorded by the anonymity beacon'],
            (object) ['label' => 'Product page views', 'icon' => '👁', 'value' => $this->countPageViews($p, '/product/%'), 'real' => true,
                'note' => 'Views on /product/ pages'],
            (object) ['label' => 'Avg. pages / visitor', 'icon' => '📑', 'value' => $this->avgPagesPerVisitor($p), 'real' => true,
                'note' => 'Page views ÷ unique visitors'],
            (object) ['label' => 'Active now', 'icon' => '🟢', 'value' => $this->activeCount(), 'real' => true,
                'note' => 'Distinct visitors in last 10 minutes'],
        ]);
    }

    protected function avgPagesPerVisitor(array $p): ?float
    {
        $visitors = $this->uniqueVisitors($p);
        if ($visitors <= 0) {
            return null;
        }

        return round($this->countPageViews($p) / $visitors, 1);
    }

    /**
     * Real-time panel: distinct visitors active in the last 10 minutes plus a
     * short tail of the most recent page views. Returns null-safe values so the
     * panel never fabricates numbers.
     */
    protected function realtime(Request $request): array
    {
        $since = now()->subMinutes(10);
        $active = (int) PageView::query()
            ->where('viewed_at', '>=', $since)
            ->whereNotNull('ip_hash')
            ->distinct('ip_hash')
            ->count('ip_hash');

        $recent = PageView::query()
            ->latest('viewed_at')
            ->limit(10)
            ->get()
            ->map(fn ($v) => (object) [
                'path' => $v->path,
                'family' => $v->user_agent_family,
                'viewed_at' => $v->viewed_at,
            ]);

        return [
            'active' => $active,
            'recent' => $recent,
            'now' => now()->timestamp,
            'ttl' => 10,
        ];
    }

    protected function activeCount(): int
    {
        return (int) PageView::query()
            ->where('viewed_at', '>=', now()->subMinutes(10))
            ->whereNotNull('ip_hash')
            ->distinct('ip_hash')
            ->count('ip_hash');
    }

    // ── Real metric builders ──────────────────────────────────────────────────

    protected function kpiCards(Builder $clicks, array $p): array
    {
        $total = (clone $clicks)->count();
        $delta = $this->percentDelta($total, $this->clicksQuery($p)
            ->whereBetween('clicked_at', [$p['prevStart'], $p['prevEnd']])
            ->count());

        $pageViews = $this->countPageViews($p);
        $viewDelta = $this->percentDelta(
            $pageViews,
            $this->countPageViews([
                'start' => $p['prevStart'], 'end' => $p['prevEnd'],
            ])
        );

        $uniqueVisitors = $this->uniqueVisitors($p);
        $visitorDelta = $this->percentDelta(
            $uniqueVisitors,
            $this->uniqueVisitors([
                'start' => $p['prevStart'], 'end' => $p['prevEnd'],
            ])
        );

        $productViews = $this->countPageViews($p, '/product/%');

        return [
            ['label' => 'Affiliate Clicks', 'icon' => '👆', 'value' => $total, 'delta' => $delta, 'real' => true,
                'note' => 'Outbound clicks recorded in period'],
            ['label' => 'Unique Visitors', 'icon' => '👤', 'value' => $uniqueVisitors, 'delta' => $visitorDelta, 'real' => true,
                'note' => 'Distinct hashed IPs on public pages'],
            ['label' => 'Page Views', 'icon' => '📄', 'value' => $pageViews, 'delta' => $viewDelta, 'real' => true,
                'note' => 'Views recorded by the anonymity beacon'],
            ['label' => 'Product Views', 'icon' => '👁', 'value' => $productViews, 'delta' => null, 'real' => true,
                'note' => 'Page views on /product/ pages'],
            ['label' => 'Affiliate CTR', 'icon' => '🎯', 'value' => null, 'delta' => null, 'real' => false,
                'note' => 'Needs page-view (impression) tracking per offer'],
            ['label' => 'Sessions', 'icon' => '🕘', 'value' => null, 'delta' => null, 'real' => false,
                'note' => 'Needs session boundaries'],
        ];
    }

    protected function dailyTrend(Builder $clicks, array $p): Collection
    {
        $day = $clicks->clone()
            ->selectRaw('clicked_on as day, COUNT(*) as c')
            ->groupBy('day')->orderBy('day')
            ->pluck('c', 'day');

        $series = collect();
        for ($d = $p['start']->copy(); $d->lessThanOrEqualTo($p['end']); $d->addDay()) {
            $series->push((object) [
                'day' => $d->format('Y-m-d'),
                'label' => $d->format('M j'),
                'c' => (int) ($day[$d->format('Y-m-d')] ?? 0),
            ]);
        }

        return $series;
    }

    protected function topProducts(Builder $clicks, int $limit): Collection
    {
        $clicksByProduct = $this->productClickCounts($clicks);

        return Product::query()
            ->whereIn('id', $clicksByProduct->keys())
            ->get(['id', 'name', 'slug', 'status'])
            ->sortByDesc(fn ($p) => $clicksByProduct[$p->id] ?? 0)
            ->take($limit)
            ->map(fn ($p) => (object) [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'clicks' => (int) ($clicksByProduct[$p->id] ?? 0),
                'price' => $this->bestPrice($p->id),
                'merchants' => $this->merchantCount($p->id),
            ])
            ->values();
    }

    protected function productClickCounts(Builder $clicks): Collection
    {
        return $clicks->clone()
            ->selectRaw('product_id, COUNT(*) as c')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->pluck('c', 'product_id');
    }

    protected function bestPrice(int $productId): ?float
    {
        return (float) Offer::query()
            ->where('product_id', $productId)
            ->whereNotNull('current_price')
            ->orderBy('current_price')
            ->value('current_price');
    }

    protected function merchantCount(int $productId): int
    {
        return (int) Offer::query()
            ->where('product_id', $productId)
            ->distinct('merchant_id')
            ->count('merchant_id');
    }

    protected function merchantShare(Builder $clicks, array $p): Collection
    {
        $total = (clone $clicks)->count();

        return $clicks->clone()
            ->join('merchants', 'merchants.id', '=', 'clicks.merchant_id')
            ->select('merchants.id', 'merchants.name', DB::raw('COUNT(*) as clicks'))
            ->groupBy('merchants.id', 'merchants.name')
            ->orderByDesc('clicks')
            ->get()
            ->map(fn ($r) => (object) [
                'id' => $r->id,
                'name' => $r->name,
                'clicks' => (int) $r->clicks,
                'share' => $total > 0 ? round($r->clicks / $total * 100, 1) : 0.0,
                'ctr' => null,
            ]);
    }

    /** Clicks that happened on a comparison page (referrer = /{slug}). */
    protected function comparisonClicks(Builder $clicks, int $limit): Collection
    {
        $slugs = Comparison::query()->pluck('slug');
        if ($slugs->isEmpty()) {
            return collect();
        }

        $paths = $slugs->map(fn ($s) => '/'.$s);

        $counts = $clicks->clone()
            ->whereNotNull('referrer_page')
            ->whereIn('referrer_page', $paths)
            ->selectRaw('referrer_page as path, COUNT(*) as c')
            ->groupBy('path')->pluck('c', 'path');

        return Comparison::query()
            ->get(['id', 'title', 'slug', 'status', 'featured'])
            ->sortByDesc(fn ($c) => (int) ($counts['/'.$c->slug] ?? 0))
            ->take($limit)
            ->map(fn ($c) => (object) [
                'id' => $c->id,
                'title' => $c->title,
                'slug' => $c->slug,
                'status' => $c->status,
                'featured' => (bool) $c->featured,
                'clicks' => (int) ($counts['/'.$c->slug] ?? 0),
                'views' => null,
                'ctr' => null,
            ])
            ->values();
    }

    /** Internal pages that drove affiliate clicks, bucketed honestly. */
    protected function referrerMix(Builder $clicks): Collection
    {
        $total = (clone $clicks)->count();

        $buckets = collect();
        foreach ($clicks->clone()->selectRaw('referrer_page as path, COUNT(*) as c')->groupBy('path')->get() as $row) {
            $name = $this->bucketReferrer((string) $row->path);
            $buckets[$name] = ($buckets[$name] ?? 0) + (int) $row->c;
        }

        return $buckets->sortDesc()->map(fn (int $c, string $name) => (object) [
            'name' => $name,
            'clicks' => $c,
            'share' => $total > 0 ? round($c / $total * 100, 1) : 0.0,
        ])->values();
    }

    protected function bucketReferrer(string $path): string
    {
        return match (true) {
            $path === '' || str_starts_with($path, '/go/') => 'Direct entry',
            str_starts_with($path, '/product/') => 'Product pages',
            str_starts_with($path, '/category/') => 'Category pages',
            str_starts_with($path, '/landing/') => 'Landing pages',
            str_starts_with($path, '/compare') => 'Compare tool',
            str_starts_with($path, '/deals') => 'Deals',
            str_starts_with($path, '/price-drops') => 'Price drops',
            str_starts_with($path, '/search') => 'Search results',
            $path === '/' => 'Homepage',
            default => 'Other pages',
        };
    }

    protected function clickerStats(Builder $clicks, array $p): Collection
    {
        $total = (clone $clicks)->count();
        $unique = (clone $clicks)->distinct('ip_hash')->count('ip_hash');

        return collect([
            (object) ['label' => 'Unique clickers (hashed)', 'value' => $unique, 'note' => 'Distinct hashed IPs, no personal data', 'real' => true],
            (object) ['label' => 'Affiliate clicks', 'value' => $total, 'note' => 'Outbound clicks recorded', 'real' => true],
            (object) ['label' => 'Page views', 'value' => $this->countPageViews($p), 'note' => 'Views recorded by the anonymity beacon', 'real' => true],
            (object) ['label' => 'Unique visitors', 'value' => $this->uniqueVisitors($p), 'note' => 'Distinct hashed IPs on public pages', 'real' => true],
            (object) ['label' => 'Sessions', 'value' => null, 'note' => 'Awaiting session boundaries', 'real' => false],
        ]);
    }

    protected function funnel(Builder $clicks, array $p): array
    {
        $total = (clone $clicks)->count();

        return [
            ['label' => 'Unique visitors', 'value' => $this->uniqueVisitors($p), 'real' => true],
            ['label' => 'Product page views', 'value' => $this->countPageViews($p, '/product/%'), 'real' => true],
            ['label' => 'Affiliate clicks', 'value' => $total, 'real' => true],
            ['label' => 'Merchant visits (outbound redirects)', 'value' => $total, 'real' => true],
        ];
    }

    /** Real gaps derived from catalog data only — never fabricated. */
    protected function opportunities(Builder $clicks): array
    {
        $items = [];

        $noAffiliate = (int) Product::query()
            ->where('status', 'published')
            ->whereDoesntHave('offers', fn ($q) => $q->whereNotNull('affiliate_url')->where('affiliate_url', '<>', ''))
            ->count();
        if ($noAffiliate > 0) {
            $items[] = "{$noAffiliate} published product(s) have no affiliate URL — they earn no commission.";
        }

        $noPrice = (int) Offer::query()
            ->where('status', 'active')
            ->whereNull('current_price')
            ->distinct('product_id')->count('product_id');
        if ($noPrice > 0) {
            $items[] = "{$noPrice} product(s) have offers without a current price — update or hide them.";
        }

        $noDescription = (int) Product::query()
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('description')->orWhere('description', ''))
            ->count();
        if ($noDescription > 0) {
            $items[] = "{$noDescription} published product(s) have no description.";
        }

        $topComparison = $this->comparisonClicks($clicks, 1)->first();
        if ($topComparison && $topComparison->clicks > 0) {
            $items[] = "“{$topComparison->title}” is the top comparison for affiliate clicks ({$topComparison->clicks} click(s)).";
        }

        $topCategory = $this->topCategoryName($clicks);
        if ($topCategory) {
            $items[] = "“{$topCategory}” is receiving the most affiliate clicks this period.";
        }

        return $items;
    }

    protected function topCategoryName(Builder $clicks): ?string
    {
        return $clicks->clone()
            ->join('products', 'products.id', '=', 'clicks.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name, COUNT(*) as c')
            ->groupBy('categories.name')->orderByDesc('c')
            ->value('categories.name');
    }

    protected function conversions(array $p): array
    {
        $base = AffiliateConversion::query();

        return [
            'approved' => (float) (clone $base)->where('status', 'approved')->sum('commission_amount'),
            'pending' => (float) (clone $base)->where('status', 'pending')->sum('commission_amount'),
            'count' => (clone $base)->count(),
            'rows' => AffiliateConversion::with('merchant:id,name')->latest('imported_at')->limit(10)->get(),
        ];
    }

    protected function percentDelta(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    protected function sortSpec(Request $request): array
    {
        $key = (string) $request->query('sort', 'clicks');
        if (! in_array($key, ['clicks', 'name', 'price', 'merchants', 'status'], true)) {
            $key = 'clicks';
        }

        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        return ['key' => $key, 'dir' => $dir];
    }

    protected function productPerformance(Collection $clickCounts, Request $request): Collection
    {
        $spec = $this->sortSpec($request);

        $offerAgg = Offer::query()
            ->selectRaw('product_id, MIN(current_price) as best, COUNT(DISTINCT merchant_id) as merchants,
                SUM(CASE WHEN (affiliate_url IS NULL OR affiliate_url = ?) THEN 0 ELSE 1 END) as with_affiliate')
            ->setBindings([''], 'select')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $rows = Product::query()
            ->where('status', 'published')
            ->get(['id', 'name', 'slug', 'status', 'category_id', 'updated_at'])
            ->map(function ($p) use ($clickCounts, $offerAgg) {
                $agg = $offerAgg[$p->id] ?? null;

                return (object) [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'clicks' => (int) ($clickCounts[$p->id] ?? 0),
                    'price' => $agg && $agg->best !== null ? (float) $agg->best : null,
                    'merchants' => (int) ($agg->merchants ?? 0),
                    'hasAffiliate' => (int) ($agg->with_affiliate ?? 0) > 0,
                    'hasPrice' => $agg && $agg->best !== null,
                    'updated' => $p->updated_at,
                ];
            });

        $dir = $spec['dir'] === 'asc' ? 'asc' : 'desc';

        $rows = match ($spec['key']) {
            'name' => $rows->sortBy('name', SORT_NATURAL),
            'price' => $rows->sortBy(fn ($r) => $r->price ?? PHP_FLOAT_MAX),
            'merchants' => $rows->sortBy(fn ($r) => $r->merchants),
            'status' => $rows->sortBy(fn ($r) => $r->hasAffiliate),
            default => $rows->sortBy(fn ($r) => $r->clicks),
        };

        if ($dir === 'desc') {
            $rows = $rows->reverse();
        }

        return $rows->values()->take(250);
    }
}
