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

class PaymentSummaryReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting
    ) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $report = $this->reporting->paymentCollectionSummary($from, $to);

        return view('modules.reports.payment-summary', compact('report', 'from', 'to'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->paymentCollectionSummary($from, $to);
        $filename    = sprintf('payment-summary-%s-to-%s.csv', $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Payment Method', 'Transactions', 'Amount']);

            foreach ($report['by_method'] as $row) {
                fputcsv($handle, [
                    strtoupper(str_replace('_', ' ', $row['method'])),
                    $row['count'],
                    $row['total'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['TOTAL', '', $report['grand_total']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Folio (room account)', '', $report['folio_total']]);
            fputcsv($handle, ['Direct POS', '', $report['direct_total']]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->paymentCollectionSummary($from, $to);
        $filename    = sprintf('payment-summary-%s-to-%s.xls', $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report): void {
            echo '<table border="1"><thead><tr><th>Payment Method</th><th>Transactions</th><th>Amount</th></tr></thead><tbody>';

            foreach ($report['by_method'] as $row) {
                echo '<tr>';
                echo '<td>'.e(ucwords(str_replace('_', ' ', $row['method']))).'</td>';
                echo '<td>'.e($row['count']).'</td>';
                echo '<td>'.e($row['total']).'</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '<tfoot>';
            echo '<tr><td><strong>TOTAL</strong></td><td></td><td><strong>'.e($report['grand_total']).'</strong></td></tr>';
            echo '<tr><td></td><td></td><td></td></tr>';
            echo '<tr><td>Folio (room account)</td><td></td><td>'.e($report['folio_total']).'</td></tr>';
            echo '<tr><td>Direct POS</td><td></td><td>'.e($report['direct_total']).'</td></tr>';
            echo '</tfoot></table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function exportPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->paymentCollectionSummary($from, $to);
        $filename    = sprintf('payment-summary-%s-to-%s.pdf', $report['from'], $report['to']);

        return Pdf::loadView('modules.reports.pdf.payment-summary', compact('report'))
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
