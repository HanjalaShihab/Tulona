<?php

use App\Console\Commands\SyncMerchants;
use Illuminate\Support\Facades\Schedule;

// Scheduled offer synchronization every 6 hours (Build.md §26)
Schedule::command(SyncMerchants::class)->everySixHours();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
