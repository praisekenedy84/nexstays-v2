<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Actions\SendDailyReportEmail;
use App\Domain\Shared\Services\ReportDeliverySettingsService;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Throwable;

class NexstaySendDailyReportsCommand extends Command
{
    protected $signature = 'nexstay:send-daily-reports {--force : Send now and ignore configured daily time}';

    protected $description = 'Send daily report emails for tenants based on report delivery settings';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        Tenant::query()->cursor()->each(function (Tenant $tenant) use ($force, &$sent, &$skipped, &$failed): void {
            try {
                $tenant->run(function () use ($force, $tenant, &$sent, &$skipped): void {
                    $settingsService = app(ReportDeliverySettingsService::class);

                    if (! $force && ! $settingsService->dueForDispatch(now())) {
                        $skipped++;
                        return;
                    }

                    $didSend = app(SendDailyReportEmail::class)->execute();

                    if ($didSend) {
                        $sent++;
                        return;
                    }

                    $this->warn(sprintf('Skipped tenant "%s": recipient email is not configured.', $tenant->id));
                    $skipped++;
                });
            } catch (Throwable $exception) {
                $failed++;
                $this->error(sprintf('Failed for tenant "%s": %s', $tenant->id, $exception->getMessage()));
            }
        });

        $this->info(sprintf('Daily reports complete. sent=%d skipped=%d failed=%d', $sent, $skipped, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
