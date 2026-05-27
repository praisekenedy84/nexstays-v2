<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Shared\Services\DivisionSalesService;
use App\Domain\Shared\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunNightAuditJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $lock = Cache::lock('night_audit', 300);

        if (! $lock->get()) {
            Log::warning('Night audit skipped — lock not acquired.');

            return;
        }

        try {
            Log::info('Night audit started for tenant', ['tenant' => tenant('id')]);

            // Snapshot yesterday's division sales for trending
            app(DivisionSalesService::class)->takeSnapshot(now()->subDay());

            app(NotificationService::class)->notify(
                type:  'night_audit',
                title: 'Night audit completed',
                body:  'Daily snapshot written for '.now()->subDay()->format('d M Y').'.',
                data:  ['date' => now()->subDay()->toDateString()],
            );

            Log::info('Night audit completed.', ['tenant' => tenant('id')]);
        } finally {
            $lock->release();
        }
    }
}
