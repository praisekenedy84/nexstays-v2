<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\DivisionSalesService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesSummaryReportController extends Controller
{
    public function __construct(
        private readonly DivisionSalesService $divisionSales,
    ) {}

    public function index(Request $request): View
    {
        [$period, $date, $report] = $this->buildReport($request);

        return view('modules.reports.sales-summary', compact('report', 'period', 'date'));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $report   = $this->buildReport($request)[2];
        $filename = sprintf('sales-summary-%s-%s-to-%s.xls', $report['period'], $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report): void {
            $currency = config('nexstay.currency.default', 'TZS');

            echo '<table border="1">';
            echo '<tr><th colspan="8"><strong>Sales Summary</strong></th></tr>';
            echo '<tr><td colspan="8">Period: '.e($report['period_label']).' ('.e($report['from']).' — '.e($report['to']).')</td></tr>';
            echo '<tr><td colspan="8"></td></tr>';

            echo '<tr><th>Division</th><th>Amount ('.e($currency).')</th></tr>';
            foreach ($this->divisionRows($report['summary']) as $row) {
                echo '<tr><td>'.e($row['label']).'</td><td>'.e(number_format($row['amount'], 0)).'</td></tr>';
            }
            echo '<tr><td><strong>Total posted sales</strong></td><td><strong>'.e(number_format($report['summary']['total'], 0)).'</strong></td></tr>';
            echo '<tr><td>Payments collected</td><td>'.e(number_format($report['summary']['payments_collected'], 0)).'</td></tr>';
            echo '<tr><td>Room nights occupied</td><td>'.e($report['summary']['room_nights']).'</td></tr>';

            if ($report['daily_rows'] !== []) {
                echo '<tr><td colspan="8"></td></tr>';
                echo '<tr><th>Date</th><th>Rooms</th><th>Restaurant</th><th>Bar &amp; lounge</th><th>Ancillary</th><th>Total</th><th>Payments</th><th>Room nights</th></tr>';

                foreach ($report['daily_rows'] as $row) {
                    echo '<tr>';
                    echo '<td>'.e($row['date_label']).'</td>';
                    echo '<td>'.e(number_format($row['rooms'], 0)).'</td>';
                    echo '<td>'.e(number_format($row['restaurant'], 0)).'</td>';
                    echo '<td>'.e(number_format($row['bar'], 0)).'</td>';
                    echo '<td>'.e(number_format($row['ancillary'], 0)).'</td>';
                    echo '<td>'.e(number_format($row['total'], 0)).'</td>';
                    echo '<td>'.e(number_format($row['payments_collected'], 0)).'</td>';
                    echo '<td>'.e($row['room_nights']).'</td>';
                    echo '</tr>';
                }
            }

            echo '</table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function exportPdf(Request $request): Response
    {
        $report   = $this->buildReport($request)[2];
        $filename = sprintf('sales-summary-%s-%s-to-%s.pdf', $report['period'], $report['from'], $report['to']);

        return Pdf::loadView('modules.reports.pdf.sales-summary', compact('report'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /** @return array{0: string, 1: Carbon, 2: array} */
    private function buildReport(Request $request): array
    {
        $period = $this->resolvePeriod($request);
        $date   = $request->filled('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : now()->startOfDay();

        return [$period, $date, $this->divisionSales->salesSummaryReport($period, $date)];
    }

    private function resolvePeriod(Request $request): string
    {
        $period = $request->input('period', 'daily');

        return in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';
    }

    /** @return list<array{label: string, amount: float}> */
    private function divisionRows(array $summary): array
    {
        return [
            ['label' => 'Rooms (posted)', 'amount' => $summary['rooms']],
            ['label' => 'Restaurant', 'amount' => $summary['restaurant']],
            ['label' => 'Bar & lounge', 'amount' => $summary['bar']],
            ['label' => 'Ancillary', 'amount' => $summary['ancillary']],
        ];
    }
}
