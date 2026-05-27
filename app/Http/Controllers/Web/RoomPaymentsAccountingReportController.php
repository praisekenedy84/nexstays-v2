<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\ReportingService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class RoomPaymentsAccountingReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting
    ) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $report = $this->reporting->roomPaymentsAccounting($from, $to);

        return view('modules.reports.room-payments-accounting', compact('report', 'from', 'to'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $report = $this->reporting->roomPaymentsAccounting($from, $to);
        $filename = sprintf('room-payments-accounting-%s-to-%s.csv', $report['from'], $report['to']);
        $headings = $this->exportHeadings();

        return response()->streamDownload(function () use ($report, $headings): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headings);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, $this->exportRow($row));
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL',
                '',
                '',
                '',
                '',
                '',
                $report['totals']['room_nights'],
                $report['totals']['room_revenue'],
                $report['totals']['folio_charges'],
                $report['totals']['payments_received'],
                $report['totals']['outstanding_balance'],
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $report = $this->reporting->roomPaymentsAccounting($from, $to);
        $filename = sprintf('room-payments-accounting-%s-to-%s.xls', $report['from'], $report['to']);
        $headings = $this->exportHeadings();

        return response()->streamDownload(function () use ($report, $headings): void {
            echo '<table border="1"><thead><tr>';
            foreach ($headings as $heading) {
                echo '<th>'.e($heading).'</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($report['rows'] as $row) {
                echo '<tr>';
                foreach ($this->exportRow($row) as $value) {
                    echo '<td>'.e((string) $value).'</td>';
                }
                echo '</tr>';
            }

            echo '<tr>';
            echo '<td><strong>TOTAL</strong></td>';
            echo '<td></td><td></td><td></td><td></td><td></td>';
            echo '<td><strong>'.e((string) $report['totals']['room_nights']).'</strong></td>';
            echo '<td><strong>'.e((string) $report['totals']['room_revenue']).'</strong></td>';
            echo '<td><strong>'.e((string) $report['totals']['folio_charges']).'</strong></td>';
            echo '<td><strong>'.e((string) $report['totals']['payments_received']).'</strong></td>';
            echo '<td><strong>'.e((string) $report['totals']['outstanding_balance']).'</strong></td>';
            echo '</tr>';

            echo '</tbody></table>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->roomPaymentsAccounting($from, $to);
        $filename    = sprintf('room-payments-accounting-%s-to-%s.pdf', $report['from'], $report['to']);

        return Pdf::loadView('modules.reports.pdf.room-payments-accounting', compact('report'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }

    /**
     * @return list<string>
     */
    private function exportHeadings(): array
    {
        return [
            'Booking Ref',
            'Status',
            'Guest',
            'Room',
            'Check In',
            'Check Out',
            'Stay Nights',
            'Room Revenue',
            'Folio Charges',
            'Payments Received',
            'Outstanding Balance',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<int|string|null>
     */
    private function exportRow(array $row): array
    {
        return [
            $row['reservation']->booking_ref,
            $row['reservation']->status,
            $row['guest_name'],
            $row['room_number'],
            $row['reservation']->check_in_date?->format('Y-m-d'),
            $row['reservation']->check_out_date?->format('Y-m-d'),
            $row['stay_nights'],
            $row['room_revenue'],
            $row['folio_charges'],
            $row['payments_received'],
            $row['outstanding_balance'],
        ];
    }
}
