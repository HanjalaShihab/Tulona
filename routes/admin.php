<?php

use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ComparisonController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ScrapePostController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'form'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'authenticate'])->middleware('throttle:10,1')->name('admin.authenticate');
});

// Laravel's auth middleware redirects guests to route('login') — alias it to
// the admin login form since only admins can authenticate (Build.md §6).
Route::get('/admin/login', [AuthController::class, 'form'])->middleware('guest')->name('login');

Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout')->middleware('throttle:30,1');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active.admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Catalog management
    Route::middleware('can:manage-products')->group(function () {
        Route::resource('products', ProductController::class)->except('show');
        Route::post('products/{product}/offers', [ProductController::class, 'storeOffer'])->name('products.offers.store');
        Route::put('offers/{offer}', [ProductController::class, 'updateOffer'])->name('offers.update');
        Route::delete('offers/{offer}', [ProductController::class, 'destroyOffer'])->name('offers.destroy');
        Route::post('products/{product}/attributes', [ProductController::class, 'updateAttributes'])->name('products.attributes');
        Route::post('products/{product}/images', [ProductController::class, 'storeImage'])->name('products.images.store');
        Route::put('images/{image}', [ProductController::class, 'updateImage'])->name('images.update');
        Route::post('images/{image}/main', [ProductController::class, 'makeMainImage'])->name('images.main');
        Route::post('images/{image}/move', [ProductController::class, 'moveImage'])->name('images.move');
        Route::delete('images/{image}', [ProductController::class, 'destroyImage'])->name('images.destroy');
        Route::post('products/bulk', [ProductController::class, 'bulkAction'])->name('products.bulk');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('brands', BrandController::class)->except('show');
    });

    // Scrape & Post — single product detail → edit → publish into a category (§new)
    Route::middleware('can:manage-products')->group(function () {
        Route::get('scrape-post', [ScrapePostController::class, 'index'])->name('scrape-post.index');
        Route::post('scrape-post/scrape', [ScrapePostController::class, 'scrape'])->name('scrape-post.scrape');
        Route::get('scrape-post/edit', [ScrapePostController::class, 'edit'])->name('scrape-post.edit');
        Route::post('scrape-post/post', [ScrapePostController::class, 'post'])->name('scrape-post.post');
        Route::post('scrape-post/reset', [ScrapePostController::class, 'reset'])->name('scrape-post.reset');
    });

    // Affiliate offers & link generation (§19–§22)
    Route::middleware('can:manage-merchants')->group(function () {
        Route::get('affiliate', [AffiliateController::class, 'index'])->name('affiliate.index');
        Route::post('affiliate/bulk-generate', [AffiliateController::class, 'bulkGenerate'])->name('affiliate.bulk-generate');
        Route::get('affiliate/runs/{run}/progress', [AffiliateController::class, 'generationProgress'])->name('affiliate.generation-progress');
        Route::get('affiliate/{affiliateOffer}', [AffiliateController::class, 'show'])->name('affiliate.show');
        Route::get('affiliate/{affiliateOffer}/edit', [AffiliateController::class, 'edit'])->name('affiliate.edit');
        Route::put('affiliate/{affiliateOffer}', [AffiliateController::class, 'update'])->name('affiliate.update');
        Route::post('affiliate/{affiliateOffer}/open-generator', [AffiliateController::class, 'openGenerator'])->name('affiliate.open-generator');
        Route::post('affiliate/{affiliateOffer}/verify', [AffiliateController::class, 'markVerified'])->name('affiliate.verify');
        Route::get('affiliate/{affiliateOffer}/generations', [AffiliateController::class, 'generationHistory'])->name('affiliate.generations');
    });

    // Merchants & sync
    Route::middleware('can:manage-merchants')->group(function () {
        Route::resource('merchants', MerchantController::class)->except('show');
        Route::post('merchants/{merchant}/sync', [SyncController::class, 'run'])->name('merchants.sync');
    });

    // Content
    Route::middleware('can:manage-content')->group(function () {
        Route::resource('articles', ArticleController::class)->except('show');
        Route::resource('landing-pages', LandingPageController::class)->except('show');
        Route::put('settings/homepage', [SettingController::class, 'updateHomepage'])->name('settings.homepage');
    });

    // Comparisons (§29–§37)
    Route::middleware('can:manage-content')->group(function () {
        Route::resource('comparisons', ComparisonController::class)->except('show');
        Route::post('comparisons/{comparison}/add-offer', [ComparisonController::class, 'addOffer'])->name('comparisons.add-offer');
        Route::put('comparisons/{comparison}/sync-products', [ComparisonController::class, 'syncProducts'])->name('comparisons.sync-products');
        Route::put('comparisons/{comparison}/sync-offer-overrides', [ComparisonController::class, 'syncOfferOverrides'])->name('comparisons.sync-offer-overrides');
        Route::post('comparisons/{comparison}/scrape', [ComparisonController::class, 'scrape'])->name('comparisons.scrape');
        Route::post('comparisons/{comparison}/attach-common', [ComparisonController::class, 'attachCommon'])->name('comparisons.attach-common');
    });

    // Imports (§16: upload, URL scrape, preview confirm, cancel)
    Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('imports', [ImportController::class, 'upload'])->name('imports.upload');   // step 1-2: upload + validate + preview
    Route::post('imports/scrape', [ImportController::class, 'scrape'])->name('imports.scrape');   // §14 URL import → queued
    Route::post('imports/{batch}/confirm', [ImportController::class, 'confirm'])->name('imports.confirm'); // step 3: queue job
    Route::post('imports/{batch}/selected', [ImportController::class, 'selected'])->name('imports.selected'); // §16 import selected
    Route::post('imports/{batch}/remove-selected', [ImportController::class, 'removeSelected'])->name('imports.remove-selected'); // §16 drop selected from list
    Route::delete('imports/{batch}/items/{item}', [ImportController::class, 'destroyItem'])->name('imports.items.destroy'); // §16 drop single item
    Route::post('imports/{batch}/cancel', [ImportController::class, 'cancel'])->name('imports.cancel');     // §16 cancel preview
    Route::post('imports/{batch}/retry', [ImportController::class, 'retry'])->name('imports.retry');         // §15 retry failed batch
    Route::post('imports/{batch}/retry-failed', [ImportController::class, 'retryFailedItems'])->name('imports.retry-failed'); // §16 resume failed items
    Route::get('imports/{batch}', [ImportController::class, 'show'])->name('imports.show');               // results

    // Analytics & users
    Route::middleware('can:view-analytics')->get('analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::middleware('can:manage-users')->resource('users', UserController::class)->only('index', 'store', 'update', 'destroy');
});
