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

class OccupancyReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting
    ) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $report = $this->reporting->occupancy($from, $to);

        return view('modules.reports.occupancy', compact('report', 'from', 'to'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->occupancy($from, $to);
        $filename    = sprintf('occupancy-%s-to-%s.csv', $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Date', 'Rooms Available', 'Rooms Occupied', 'Occupancy %', 'Revenue', 'ADR', 'RevPAR']);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['rooms_available'],
                    $row['rooms_occupied'],
                    $row['occupancy_pct'],
                    $row['revenue'],
                    $row['adr'],
                    $row['revpar'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL / AVG',
                $report['totals']['rooms_available'],
                $report['totals']['room_nights_sold'],
                $report['totals']['avg_occupancy_pct'],
                $report['totals']['total_revenue'],
                $report['totals']['adr'],
                $report['totals']['revpar'],
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->occupancy($from, $to);
        $filename    = sprintf('occupancy-%s-to-%s.xls', $report['from'], $report['to']);
        $headings    = ['Date', 'Rooms Available', 'Rooms Occupied', 'Occupancy %', 'Revenue', 'ADR', 'RevPAR'];

        return response()->streamDownload(function () use ($report, $headings): void {
            echo '<table border="1"><thead><tr>';
            foreach ($headings as $h) {
                echo '<th>'.e($h).'</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($report['rows'] as $row) {
                echo '<tr>';
                echo '<td>'.e($row['date']).'</td>';
                echo '<td>'.e($row['rooms_available']).'</td>';
                echo '<td>'.e($row['rooms_occupied']).'</td>';
                echo '<td>'.e($row['occupancy_pct']).'%</td>';
                echo '<td>'.e($row['revenue']).'</td>';
                echo '<td>'.e($row['adr']).'</td>';
                echo '<td>'.e($row['revpar']).'</td>';
                echo '</tr>';
            }

            $t = $report['totals'];
            echo '<tr><td><strong>TOTAL / AVG</strong></td>';
            echo '<td>'.e($t['rooms_available']).'</td>';
            echo '<td>'.e($t['room_nights_sold']).'</td>';
            echo '<td>'.e($t['avg_occupancy_pct']).'%</td>';
            echo '<td><strong>'.e($t['total_revenue']).'</strong></td>';
            echo '<td>'.e($t['adr']).'</td>';
            echo '<td>'.e($t['revpar']).'</td>';
            echo '</tr>';

            echo '</tbody></table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function exportPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $report      = $this->reporting->occupancy($from, $to);
        $filename    = sprintf('occupancy-%s-to-%s.pdf', $report['from'], $report['to']);

        return Pdf::loadView('modules.reports.pdf.occupancy', compact('report'))
            ->setPaper('a4', 'landscape')
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
