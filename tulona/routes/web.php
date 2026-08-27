<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api\AlternativesController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\PriceHistoryController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\DealsController;
use App\Http\Controllers\GoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PriceDropController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicComparisonController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// ── Public site ─────────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/suggest', [SearchController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');

Route::get('/products', [CategoryController::class, 'all'])->name('products.index');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/brand/{slug}', [BrandController::class, 'show'])->name('brands.show');
Route::get('/merchant/{slug}', [MerchantController::class, 'show'])->name('merchants.show');

Route::get('/deals', [DealsController::class, 'index'])->name('deals.index');
Route::get('/price-drops', [PriceDropController::class, 'index'])->name('drops.index');
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');

Route::get('/guides', [ArticleController::class, 'guides'])->name('guides.index');
Route::get('/reviews', [ArticleController::class, 'reviews'])->name('reviews.index');
Route::get('/guides/{slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/reviews/{slug}', fn (string $slug) => redirect(route('articles.show', $slug), 301));

// Tracked affiliate redirect (§5) — rate-limited, noindex
Route::get('/go/{product}/{merchant}', [GoController::class, 'redirect'])
    ->middleware('throttle:60,1')
    ->name('go.redirect');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');

// Published comparisons — clean slugs (§37); resolved before the trust-page catch-all.
Route::get('/{comparison}', [PublicComparisonController::class, 'show'])
    ->where('comparison', '^(?!(?:admin|'.implode('|', array_map(fn ($p) => preg_quote($p, '/'), array_keys(PageController::PAGES))).')$)[^/]+$')
    ->name('comparisons.show');

// Trust & transparency (§43) — plain slugs kept out of other route namespaces
Route::get('/{slug}', [PageController::class, 'show'])
    ->whereIn('slug', array_keys(PageController::PAGES))
    ->name('pages.show');

// ── Read-only JSON API (§54) — future mobile app / partners hook in here ────
Route::prefix('api')->middleware('throttle:120,1')->group(function () {
    Route::get('/products', [ApiController::class, 'products']);
    Route::get('/products/{slug}', [ApiController::class, 'product']);
    Route::get('/products/{slug}/offers', [ApiController::class, 'offers']);
    Route::get('/products/{slug}/price-history', [PriceHistoryController::class, 'show']);
    Route::get('/products/{slug}/alternatives', [AlternativesController::class, 'show']);
    Route::get('/categories', [ApiController::class, 'categories']);
    Route::get('/categories/{slug}/products', [ApiController::class, 'categoryProducts']);
    Route::get('/brands/{slug}', [ApiController::class, 'brand']);
    Route::get('/merchants', [ApiController::class, 'merchants']);
    Route::get('/merchants/{slug}', [ApiController::class, 'merchant']);
    Route::get('/deals', [ApiController::class, 'deals']);
    Route::get('/price-drops', [ApiController::class, 'priceDrops']);
    Route::get('/search', [ApiController::class, 'search']);
    Route::get('/compare', [ApiController::class, 'compare']);
});

// ── Admin (only admins authenticate; public browsing is anonymous §6) ───────
require __DIR__.'/admin.php';
