<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Click;
use App\Models\ImportBatch;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Admin overview (§30). */
    public function index(): View
    {
        return view('admin.dashboard', [
            'products' => Product::count(),
            'offers' => Offer::where('status', 'active')->count(),
            'merchants' => Merchant::where('status', 'active')->count(),
            'categories' => Category::count(),
            'articles' => Article::published()->count(),
            'clicksTotal' => Click::count(),
            'clicksToday' => Click::whereDate('clicked_at', today())->count(),
            'failedImports' => ImportBatch::where('status', 'failed')->count(),
            'recentImports' => ImportBatch::latest()->limit(5)->get(),
            'syncHealth' => SyncLog::latest()->limit(6)->get(),
            'topProducts' => Product::with('brand')->withCount('clicks')->orderByDesc('clicks_count')->limit(5)->get(),
        ]);
    }
}
