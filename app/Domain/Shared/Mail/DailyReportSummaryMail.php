<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyReportSummaryMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $roomReservationReport
     * @param  array<string, mixed>  $roomAccountingReport
     * @param  array<string, mixed>  $fbRevenueReport
     */
    public function __construct(
        public readonly string $reportDate,
        public readonly array $roomReservationReport,
        public readonly array $roomAccountingReport,
        public readonly array $fbRevenueReport,
        public readonly int $outstandingDebtsCount,
        public readonly string $outstandingDebtsTotal
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('NexStay Daily Report - %s', $this->reportDate),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reports.daily-summary',
        );
    }
}
