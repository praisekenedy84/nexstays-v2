<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\ReportingService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoomReservationReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting
    ) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $report = $this->reporting->roomReservationFinance($from, $to);

        return view('modules.reports.room-reservations', compact('report', 'from', 'to'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->roomReservationFinance($from, $to);
        $filename    = sprintf('room-reservations-%s-to-%s.csv', $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Period', $report['from'], 'to', $report['to']]);
            fputcsv($handle, []);

            fputcsv($handle, ['SUMMARY']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Reservations', $report['total_reservations']]);
            fputcsv($handle, ['Room Nights', $report['room_nights']]);
            fputcsv($handle, ['Room Revenue Recognized (TZS)', $report['projected_room_revenue']]);
            fputcsv($handle, ['Payments Collected (TZS)', $report['deposits_collected']]);
            fputcsv($handle, ['Balance Expected on Arrival (TZS)', $report['balance_expected_on_arrival']]);
            fputcsv($handle, []);

            fputcsv($handle, ['RESERVATION STATUS MIX']);
            fputcsv($handle, ['Status', 'Count']);
            foreach ($report['status_counts'] as $status => $count) {
                fputcsv($handle, [ucwords(str_replace('_', ' ', $status)), $count]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->roomReservationFinance($from, $to);
        $filename    = sprintf('room-reservations-%s-to-%s.xls', $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report): void {
            echo '<table border="1"><thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>';
            echo '<tr><td>Period</td><td>'.e($report['from']).' to '.e($report['to']).'</td></tr>';
            echo '<tr><td>Total Reservations</td><td>'.e($report['total_reservations']).'</td></tr>';
            echo '<tr><td>Room Nights</td><td>'.e($report['room_nights']).'</td></tr>';
            echo '<tr><td>Room Revenue Recognized (TZS)</td><td>'.e($report['projected_room_revenue']).'</td></tr>';
            echo '<tr><td>Payments Collected (TZS)</td><td>'.e($report['deposits_collected']).'</td></tr>';
            echo '<tr><td>Balance Expected on Arrival (TZS)</td><td>'.e($report['balance_expected_on_arrival']).'</td></tr>';
            echo '</tbody></table><br>';

            echo '<table border="1"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>';
            foreach ($report['status_counts'] as $status => $count) {
                echo '<tr><td>'.e(ucwords(str_replace('_', ' ', $status))).'</td><td>'.e($count).'</td></tr>';
            }
            echo '</tbody></table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function exportPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->roomReservationFinance($from, $to);
        $filename    = sprintf('room-reservations-%s-to-%s.pdf', $report['from'], $report['to']);

        return Pdf::loadView('modules.reports.pdf.room-reservations', compact('report'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveRange(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to   = $request->filled('to')   ? Carbon::parse($request->input('to'))->endOfDay()     : now()->endOfDay();

        return [$from, $to];
    }
}
