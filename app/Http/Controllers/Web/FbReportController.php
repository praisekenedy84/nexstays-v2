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

class FbReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting
    ) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $revenue = $this->reporting->fbRevenueSplit($from, $to);

        return view('modules.reports.fb-revenue', compact('revenue', 'from', 'to'));
    }

    public function exportRevenueCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $revenue     = $this->reporting->fbRevenueSplit($from, $to);
        $filename    = sprintf('fb-revenue-%s-to-%s.csv', $revenue['from'], $revenue['to']);

        return response()->streamDownload(function () use ($revenue): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Period', $revenue['from'], 'to', $revenue['to']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Category', 'Amount (TZS)']);
            fputcsv($handle, ['Food (Restaurant)', $revenue['food']]);
            fputcsv($handle, ['Drinks (Bar + Lounge)', $revenue['drinks']]);
            fputcsv($handle, ['Total F&B', $revenue['total']]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportRevenueExcel(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $revenue     = $this->reporting->fbRevenueSplit($from, $to);
        $filename    = sprintf('fb-revenue-%s-to-%s.xls', $revenue['from'], $revenue['to']);

        return response()->streamDownload(function () use ($revenue): void {
            echo '<table border="1"><thead><tr><th>Category</th><th>Amount (TZS)</th></tr></thead><tbody>';
            echo '<tr><td>Food (Restaurant)</td><td>'.e($revenue['food']).'</td></tr>';
            echo '<tr><td>Drinks (Bar + Lounge)</td><td>'.e($revenue['drinks']).'</td></tr>';
            echo '</tbody><tfoot><tr><td><strong>Total F&amp;B</strong></td><td><strong>'.e($revenue['total']).'</strong></td></tr></tfoot></table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function exportRevenuePdf(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $revenue     = $this->reporting->fbRevenueSplit($from, $to);
        $filename    = sprintf('fb-revenue-%s-to-%s.pdf', $revenue['from'], $revenue['to']);

        return Pdf::loadView('modules.reports.pdf.fb-revenue', compact('revenue'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function profitability(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $data = $this->reporting->fbProfitability($from, $to);

        return view('modules.reports.fb-profitability', compact('data', 'from', 'to'));
    }

    public function exportProfitabilityCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $data        = $this->reporting->fbProfitability($from, $to);
        $filename    = sprintf('fb-profitability-%s-to-%s.csv', $data['from'], $data['to']);

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Period', $data['from'], 'to', $data['to']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Line', 'Food (TZS)', 'Drinks (TZS)', 'Total (TZS)']);
            fputcsv($handle, ['Revenue', $data['revenue']['food'], $data['revenue']['drinks'], $data['revenue']['total']]);
            fputcsv($handle, ['COGS (theoretical)', $data['cogs']['food'], $data['cogs']['drinks'], $data['cogs']['total']]);
            fputcsv($handle, ['Gross Profit', $data['revenue']['food'] - $data['cogs']['food'], $data['revenue']['drinks'] - $data['cogs']['drinks'], $data['gross_profit']]);
            fputcsv($handle, ['Stock Purchases', $data['purchases']['food'], $data['purchases']['drinks'], $data['purchases']['total']]);
            fputcsv($handle, ['Outlet Expenses', '', '', $data['outlet_expenses']]);
            fputcsv($handle, ['Net Contribution', '', '', $data['net_contribution']]);
            fputcsv($handle, ['Gross Margin %', '', '', $data['gross_margin_pct'].'%']);
            fputcsv($handle, []);

            fputcsv($handle, ['TOP SELLING ITEMS']);
            fputcsv($handle, ['#', 'Item', 'Category', 'Outlet', 'Qty Sold', 'Revenue (TZS)', 'COGS (TZS)', 'Profit (TZS)']);
            foreach ($data['top_items'] as $i => $item) {
                fputcsv($handle, [
                    $i + 1,
                    $item['name'],
                    $item['category'],
                    $item['outlet_type'],
                    $item['qty_sold'],
                    $item['revenue'],
                    $item['cogs'],
                    $item['profit'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['ROOM TYPE PERFORMANCE']);
            fputcsv($handle, ['Room Type', 'Reservations', 'Room Nights', 'Revenue (TZS)', 'Avg/Night (TZS)']);
            foreach ($data['top_room_types'] as $rt) {
                fputcsv($handle, [
                    $rt['name'],
                    $rt['reservations'],
                    $rt['room_nights'],
                    $rt['revenue'],
                    $rt['room_nights'] > 0 ? round($rt['revenue'] / $rt['room_nights']) : 0,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportProfitabilityExcel(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $data        = $this->reporting->fbProfitability($from, $to);
        $filename    = sprintf('fb-profitability-%s-to-%s.xls', $data['from'], $data['to']);

        return response()->streamDownload(function () use ($data): void {
            // Profitability matrix
            echo '<table border="1"><thead><tr><th>Line</th><th>Food (TZS)</th><th>Drinks (TZS)</th><th>Total (TZS)</th></tr></thead><tbody>';
            echo '<tr><td>Revenue</td><td>'.e($data['revenue']['food']).'</td><td>'.e($data['revenue']['drinks']).'</td><td>'.e($data['revenue']['total']).'</td></tr>';
            echo '<tr><td>COGS (theoretical)</td><td>'.e($data['cogs']['food']).'</td><td>'.e($data['cogs']['drinks']).'</td><td>'.e($data['cogs']['total']).'</td></tr>';
            echo '<tr><td>Gross Profit ('.$data['gross_margin_pct'].'% margin)</td><td>'.e($data['revenue']['food'] - $data['cogs']['food']).'</td><td>'.e($data['revenue']['drinks'] - $data['cogs']['drinks']).'</td><td>'.e($data['gross_profit']).'</td></tr>';
            echo '<tr><td>Stock Purchases</td><td>'.e($data['purchases']['food']).'</td><td>'.e($data['purchases']['drinks']).'</td><td>'.e($data['purchases']['total']).'</td></tr>';
            echo '<tr><td>Outlet Expenses</td><td></td><td></td><td>'.e($data['outlet_expenses']).'</td></tr>';
            echo '<tr><td><strong>Net Contribution</strong></td><td></td><td></td><td><strong>'.e($data['net_contribution']).'</strong></td></tr>';
            echo '</tbody></table><br>';

            // Top items
            echo '<table border="1"><thead><tr><th>#</th><th>Item</th><th>Category</th><th>Outlet</th><th>Qty Sold</th><th>Revenue (TZS)</th><th>COGS (TZS)</th><th>Profit (TZS)</th></tr></thead><tbody>';
            foreach ($data['top_items'] as $i => $item) {
                echo '<tr>';
                echo '<td>'.($i + 1).'</td>';
                echo '<td>'.e($item['name']).'</td>';
                echo '<td>'.e($item['category']).'</td>';
                echo '<td>'.e($item['outlet_type']).'</td>';
                echo '<td>'.e($item['qty_sold']).'</td>';
                echo '<td>'.e($item['revenue']).'</td>';
                echo '<td>'.e($item['cogs']).'</td>';
                echo '<td>'.e($item['profit']).'</td>';
                echo '</tr>';
            }
            echo '</tbody></table><br>';

            // Room type performance
            echo '<table border="1"><thead><tr><th>Room Type</th><th>Reservations</th><th>Room Nights</th><th>Revenue (TZS)</th><th>Avg/Night</th></tr></thead><tbody>';
            foreach ($data['top_room_types'] as $rt) {
                $avg = $rt['room_nights'] > 0 ? round($rt['revenue'] / $rt['room_nights']) : 0;
                echo '<tr>';
                echo '<td>'.e($rt['name']).'</td>';
                echo '<td>'.e($rt['reservations']).'</td>';
                echo '<td>'.e($rt['room_nights']).'</td>';
                echo '<td>'.e($rt['revenue']).'</td>';
                echo '<td>'.e($avg).'</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function exportProfitabilityPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $data        = $this->reporting->fbProfitability($from, $to);
        $filename    = sprintf('fb-profitability-%s-to-%s.pdf', $data['from'], $data['to']);

        return Pdf::loadView('modules.reports.pdf.fb-profitability', compact('data'))
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
