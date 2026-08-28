<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateConversion;
use App\Models\Click;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Affiliate analytics (§29) + admin analytics dashboard (§58). */
class AnalyticsController extends Controller
{
    public function index(): View
    {
        $clicksToday = Click::whereDate('clicked_at', today())->count();
        $clicksWeek = Click::whereBetween('clicked_at', [now()->startOfWeek(), now()])->count();

        return view('admin.analytics', [
            'totals' => [
                'products' => Product::count(),
                'offers' => DB::table('offers')->where('status', 'active')->count(),
                'merchants' => Merchant::where('status', 'active')->count(),
                'clicks_total' => Click::count(),
                'clicks_today' => $clicksToday,
                'clicks_week' => $clicksWeek,
                'clicks_month' => Click::whereBetween('clicked_at', [now()->startOfMonth(), now()])->count(),
                'price_drops' => DB::table('price_drop_events')->count(),
                'revenue_approved' => (float) AffiliateConversion::where('status', 'approved')->sum('commission_amount'),
                'revenue_pending' => (float) AffiliateConversion::where('status', 'pending')->sum('commission_amount'),
            ],
            // Real conversion/commission data only — clicks are never labeled revenue (§59)
            'clicksByDay' => Click::query()
                ->selectRaw('clicked_on as day, COUNT(*) as c')
                ->whereDate('clicked_on', '>=', now()->subDays(30))
                ->groupBy('day')->orderBy('day')->get(),
            'topProducts' => $this->topBy('product_id'),
            'topMerchants' => $this->topBy('merchant_id'),
            'topCategories' => DB::table('clicks')
                ->join('products', 'products.id', '=', 'clicks.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->select('categories.name', DB::raw('COUNT(*) as c'))
                ->groupBy('categories.name')->orderByDesc('c')->limit(10)->get(),
            'topLandingPages' => Click::selectRaw('COALESCE(referrer_page, "(direct)") as page, COUNT(*) as c')
                ->groupBy('page')->orderByDesc('c')->limit(10)->get(),
            'conversionRows' => AffiliateConversion::with('merchant:id,name')->latest('imported_at')->limit(20)->get(),
        ]);
    }

    protected function topBy(string $column)
    {
        if ($column === 'product_id') {
            return Click::join('products', 'products.id', '=', 'clicks.product_id')
                ->select('products.name', DB::raw('COUNT(*) as c'))
                ->groupBy('products.name')->orderByDesc('c')->limit(10)->get();
        }

        return Click::join('merchants', 'merchants.id', '=', 'clicks.merchant_id')
            ->select('merchants.name', DB::raw('COUNT(*) as c'))
            ->groupBy('merchants.name')->orderByDesc('c')->limit(10)->get();
    }
}
