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
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
    }
}
