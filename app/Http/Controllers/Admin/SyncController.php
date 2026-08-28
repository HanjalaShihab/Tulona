<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\SyncService;
use Illuminate\Http\RedirectResponse;

class SyncController extends Controller
{
    /** Manual sync trigger; scheduled syncs run via `tulona:sync` every 6h (§26). */
    public function run(Merchant $merchant, SyncService $service): RedirectResponse
    {
        $this->authorize('manage-merchants');
        $log = $service->sync($merchant);

        $msg = $log->status === 'success'
            ? "Sync finished: {$log->items_updated} offers updated."
            : 'Sync failed. Existing data kept — see the sync log.';

        return back()->with($log->status === 'success' ? 'status' : 'error', $msg);
    }
}
