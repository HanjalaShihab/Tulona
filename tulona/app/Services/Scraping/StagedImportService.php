<?php

namespace App\Services\Scraping;

use App\Models\ImportBatch;
use App\Services\Merchant\ConnectorRegistry;
use Illuminate\Support\Facades\Log;

/**
 * §16 "Import All / Import Selected" — converts confirmed, staged import_items
 * (from a URL scrape preview) into canonical products + merchant offers via the
 * merchant's connector importer. Writes batch progress after every row so the
 * admin index reflects live progress.
 */
class StagedImportService
{
    public function __construct(protected ConnectorRegistry $registry) {}

    public function process(ImportBatch $batch, ?array $itemIds = null): void
    {
        $merchant = $batch->merchant;
        abort_unless($merchant, 422, 'This batch has no merchant connected.');

        $batch->update(['status' => 'processing']);

        $connector = $this->registry->get('url');
        $config = $merchant->configuration ?? [];

        // Name-only (potential) matches are flagged for review (§32) and must
        // NOT be auto-imported — doing so re-creates duplicate products when the
        // exact match fails. Only explicit "new" and exact "matched" items go in.
        $query = $batch->items()->whereIn('status', ['new', 'matched']);

        if ($itemIds !== null) {
            $query->whereIn('id', $itemIds);
        }

        $items = $query->get();
        $counts = ['imported' => 0, 'created' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($items as $item) {
            $itemCounts = $connector->importer()->import(
                $merchant,
                [$item->normalized_data ?? []],
                $config,
                preview: false,
            );

            if (($itemCounts['matched'] ?? 0) === 0) {
                $item->update(['status' => 'skipped', 'processed_at' => now(), 'error' => 'Could not match or create product.']);
                $counts['failed']++;
            } else {
                $status = ($itemCounts['created'] ?? 0) > 0 ? 'created' : 'updated';
                $item->update(['status' => $status, 'processed_at' => now()]);
                $counts['imported']++;
                $counts[$status]++;
            }

            // Live progress for the admin progress bar (single UPDATE each row).
            $batch->update([
                'imported_count' => $counts['imported'],
                'created_count' => $counts['created'],
                'updated_count' => $counts['updated'],
                'failed_count' => $counts['failed'],
            ]);
        }

        $batch->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Log::info('Staged import completed', ['batch' => $batch->id, 'counts' => $counts]);
    }
}
