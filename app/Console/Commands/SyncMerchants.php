<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Services\SyncService;
use Illuminate\Console\Command;

class SyncMerchants extends Command
{
    protected $signature = 'tulona:sync {--merchant=* : Specific merchant slugs}';

    protected $description = 'Synchronize offers/prices from configured merchant feeds and APIs';

    public function handle(SyncService $service): int
    {
        $slugs = (array) $this->option('merchant');

        $merchants = Merchant::where('status', 'active')
            ->when($slugs, fn ($q) => $q->whereIn('slug', $slugs))
            ->get();

        if ($merchants->isEmpty()) {
            $this->info('No active merchants to synchronize.');

            return self::SUCCESS;
        }

        foreach ($merchants as $merchant) {
            $this->info("Syncing {$merchant->name}…");
            $log = $service->sync($merchant);
            $this->line("  status={$log->status} updated={$log->items_updated} failed={$log->items_failed}");
        }

        return self::SUCCESS;
    }
}
