<?php

namespace App\Providers;

use App\Connectors\Generator\ManualAffiliateLinkGenerator;
use App\Connectors\Importer\GenericProductImporter;
use App\Connectors\Mapper\MerchantCategoryMapper;
use App\Connectors\Normalizer\GenericProductNormalizer;
use App\Connectors\Parser\CsvProductParser;
use App\Contracts\Merchant\AffiliateLinkGenerator;
use App\Contracts\Merchant\CategoryMapper;
use App\Contracts\Merchant\ProductImporter;
use App\Contracts\Merchant\ProductNormalizer;
use App\Contracts\Merchant\ProductParser;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // §56 pipeline bindings — pluggable per connector without touching core.
        $this->app->bind(ProductParser::class, CsvProductParser::class);
        $this->app->bind(ProductNormalizer::class, GenericProductNormalizer::class);
        $this->app->bind(ProductImporter::class, GenericProductImporter::class);
        $this->app->bind(CategoryMapper::class, MerchantCategoryMapper::class);
        $this->app->bind(AffiliateLinkGenerator::class, ManualAffiliateLinkGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Granular admin permissions (Build.md §57). Analysts are read-only.
        Gate::define('manage-products', fn (User $u) => in_array($u->role, ['super_admin', 'product_manager']));
        Gate::define('manage-content', fn (User $u) => in_array($u->role, ['super_admin', 'content_manager']));
        Gate::define('manage-merchants', fn (User $u) => in_array($u->role, ['super_admin', 'product_manager']));
        Gate::define('run-imports', fn (User $u) => in_array($u->role, ['super_admin', 'product_manager']));
        Gate::define('view-analytics', fn (User $u) => in_array($u->role, ['super_admin', 'analyst']));
        Gate::define('manage-users', fn (User $u) => $u->isSuperAdmin());
        Gate::define('manage-settings', fn (User $u) => in_array($u->role, ['super_admin', 'content_manager']));

        // Header nav + footer store list run on every page. Cache the rows as
        // plain arrays of scalars only — never Eloquent models (the database
        // cache store on shared hosting cannot be trusted with serialized models).
        View::composer('layouts.app', function ($view): void {
            $view->with('navCategories', cache()->remember('nav.categories', 3600, fn () => Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'icon'])->map->toArray()->all()));

            $view->with('footerMerchants', cache()->remember('footer.merchants', 3600, fn () => Merchant::where('status', 'active')->orderBy('name')
                ->get(['slug', 'name'])->map->toArray()->all()));

            // Cache-bust versioned static assets (Blade compilers choke on @php()
            // shorthand mixed with @php blocks, so compute here instead).
            $view->with('assetCss', asset('css/app.css').$this->assetVersion('css/app.css'));
            $view->with('assetJs', asset('js/app.js').$this->assetVersion('js/app.js'));
        });
    }

    protected function assetVersion(string $path): string
    {
        $file = public_path($path);

        return is_file($file) ? '?v='.filemtime($file) : '';
    }
}
