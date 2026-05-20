<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\HBMS\Jobs\RunNightAuditJob;
use Illuminate\Console\Command;

class NexstayNightAuditCommand extends Command
{
    protected $signature = 'nexstay:night-audit';

    protected $description = 'Queue night audit for the current tenant context';

    public function handle(): int
    {
        RunNightAuditJob::dispatch()->onQueue('default');
        $this->info('Night audit job dispatched.');

        return self::SUCCESS;
    }
}
