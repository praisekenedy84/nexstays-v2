<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Facilities\Services\FacilityReportingService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacilityAttendanceReportController extends Controller
{
    public function __construct(
        private readonly FacilityReportingService $reporting,
    ) {}

    public function pool(Request $request): View
    {
        return $this->render($request, 'pool', 'facility-pool-report');
    }

    public function gym(Request $request): View
    {
        return $this->render($request, 'gym', 'facility-gym-report');
    }

    public function poolExportExcel(Request $request): StreamedResponse
    {
        return $this->exportExcel($request, 'pool');
    }

    public function gymExportExcel(Request $request): StreamedResponse
    {
        return $this->exportExcel($request, 'gym');
    }

    public function poolExportPdf(Request $request): Response
    {
        return $this->exportPdf($request, 'pool');
    }

    public function gymExportPdf(Request $request): Response
    {
        return $this->exportPdf($request, 'gym');
    }

    private function render(Request $request, string $facilityType, string $navId): View
    {
        [$from, $to, $report] = $this->buildReport($request, $facilityType);

        return view('modules.reports.facility-attendance', compact('report', 'from', 'to', 'navId', 'facilityType'));
    }

    /** @return array{0: string, 1: string, 2: array} */
    private function buildReport(Request $request, string $facilityType): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->startOfDay()
            : now()->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            $from->toDateString(),
            $to->toDateString(),
            $this->reporting->attendanceReport($facilityType, $from, $to),
        ];
    }

    private function exportExcel(Request $request, string $facilityType): StreamedResponse
    {
        $report   = $this->buildReport($request, $facilityType)[2];
        $filename = sprintf('%s-attendance-%s-to-%s.xls', $facilityType, $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report): void {
            echo $this->renderExcelTable($report);
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function exportPdf(Request $request, string $facilityType): Response
    {
        $report   = $this->buildReport($request, $facilityType)[2];
        $filename = sprintf('%s-attendance-%s-to-%s.pdf', $facilityType, $report['from'], $report['to']);

        return Pdf::loadView('modules.reports.pdf.facility-attendance', compact('report'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function renderExcelTable(array $report): string
    {
        $currency = config('nexstay.currency.default', 'TZS');

        ob_start();

        echo '<table border="1">';
        echo '<tr><th colspan="5"><strong>'.e($report['facility_label']).' Attendance Report</strong></th></tr>';
        echo '<tr><td colspan="5">'.e($report['from']).' — '.e($report['to']).'</td></tr>';
        echo '<tr><td>Total visits</td><td>'.e((string) $report['summary']['total_visits']).'</td></tr>';
        echo '<tr><td>Walk-in</td><td>'.e((string) $report['summary']['walk_in_visits']).'</td></tr>';
        echo '<tr><td>Hotel guests</td><td>'.e((string) $report['summary']['hotel_guest_visits']).'</td></tr>';
        echo '<tr><td>Total collected ('.e($currency).')</td><td>'.e(number_format($report['summary']['total_collected'], 0)).'</td></tr>';
        echo '<tr><td colspan="5"></td></tr>';
        echo '<tr><th>Date</th><th>Time</th><th>Visitor</th><th>Type</th><th>Amount</th><th>Settlement</th></tr>';

        foreach ($report['attendances'] as $row) {
            echo '<tr>';
            echo '<td>'.e($row->attended_at->format('d M Y')).'</td>';
            echo '<td>'.e($row->attended_at->format('H:i')).'</td>';
            echo '<td>'.e($row->visitor_name).'</td>';
            echo '<td>'.e($row->reservation_id ? 'Hotel guest' : 'Walk-in').'</td>';
            echo '<td>'.e(number_format((float) $row->amount, 0)).'</td>';
            echo '<td>'.e(ucfirst(str_replace('_', ' ', $row->settlement))).'</td>';
            echo '</tr>';
        }

        echo '</table>';

        return (string) ob_get_clean();
    }
}
