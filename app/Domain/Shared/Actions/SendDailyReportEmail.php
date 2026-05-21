<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Domain\Shared\Mail\DailyReportSummaryMail;
use App\Domain\Shared\Services\ReportDeliverySettingsService;
use App\Domain\Shared\Services\ReportingService;
use Brick\Money\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;

class SendDailyReportEmail
{
    public function __construct(
        private readonly ReportingService $reportingService,
        private readonly ReportDeliverySettingsService $settingsService
    ) {}

    public function execute(?string $recipientEmail = null, ?CarbonInterface $moment = null): bool
    {
        $settings = $this->settingsService->all();
        $email = trim($recipientEmail ?? $settings['recipient_email']);

        if ($email === '') {
            return false;
        }

        $sentAt = ($moment ?? now())->setTimezone($settings['timezone']);
        $from = $sentAt->copy()->startOfDay();
        $to = $sentAt->copy()->endOfDay();

        $roomReservationReport = $this->reportingService->roomReservationFinance($from, $to);
        $roomAccountingReport = $this->reportingService->roomPaymentsAccounting($from, $to);
        $fbRevenueReport = $this->reportingService->fbRevenueSplit($from, $to);
        $outstandingDebts = $this->reportingService->outstandingDebts();

        $debtTotal = $outstandingDebts
            ->pluck('balance')
            ->reduce(
                fn (Money $carry, Money $balance): Money => $carry->plus($balance),
                Money::of('0', config('nexstay.currency.default', 'TZS'))
            );

        Mail::to($email)->send(new DailyReportSummaryMail(
            reportDate: $sentAt->toDateString(),
            roomReservationReport: $roomReservationReport,
            roomAccountingReport: $roomAccountingReport,
            fbRevenueReport: $fbRevenueReport,
            outstandingDebtsCount: $outstandingDebts->count(),
            outstandingDebtsTotal: $debtTotal->getAmount()->__toString()
        ));

        $this->settingsService->markSent($sentAt);

        return true;
    }
}
