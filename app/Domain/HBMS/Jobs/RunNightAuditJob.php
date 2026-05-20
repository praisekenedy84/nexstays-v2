<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
            // Room charges, no-show processing, and reports are implemented in a follow-up pass.
        } finally {
            $lock->release();
        }
    }
}
