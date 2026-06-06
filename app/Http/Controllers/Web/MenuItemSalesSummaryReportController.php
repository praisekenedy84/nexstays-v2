<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Services\ReportingService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MenuItemSalesSummaryReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting,
    ) {}

    public function index(Request $request): View
    {
        [$from, $to, $outletId, $categoryId, $report] = $this->buildReport($request);

        $outlets    = Outlet::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categories = MenuCategory::query()->with('outlet')->orderBy('name')->get(['id', 'name', 'outlet_id']);

        return view('modules.reports.menu-item-sales-summary', compact(
            'report',
            'from',
            'to',
            'outletId',
            'categoryId',
            'outlets',
            'categories',
        ));
    }

    public function barIndex(Request $request): View
    {
        return $this->outletTypeIndex($request, 'bar');
    }

    public function loungeIndex(Request $request): View
    {
        return $this->outletTypeIndex($request, 'lounge');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $report   = $this->buildReport($request)[4];
        $filename = sprintf('menu-item-sales-summary-%s-to-%s.xls', $report['from'], $report['to']);

        return $this->streamExcelDownload($report, $filename);
    }

    public function barExportExcel(Request $request): StreamedResponse
    {
        return $this->outletTypeExportExcel($request, 'bar');
    }

    public function loungeExportExcel(Request $request): StreamedResponse
    {
        return $this->outletTypeExportExcel($request, 'lounge');
    }

    public function exportPdf(Request $request): Response
    {
        $report   = $this->buildReport($request)[4];
        $filename = sprintf('menu-item-sales-summary-%s-to-%s.pdf', $report['from'], $report['to']);

        return $this->downloadPdf($report, $filename);
    }

    public function barExportPdf(Request $request): Response
    {
        return $this->outletTypeExportPdf($request, 'bar');
    }

    public function loungeExportPdf(Request $request): Response
    {
        return $this->outletTypeExportPdf($request, 'lounge');
    }

    private function outletTypeIndex(Request $request, string $outletType): View
    {
        abort_unless(in_array($outletType, ['bar', 'lounge'], true), 404);

        [$from, $to, $outletId, $categoryId, $report] = $this->buildReport($request, $outletType);

        $outlets = Outlet::query()
            ->where('is_active', true)
            ->where('type', $outletType)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = MenuCategory::query()
            ->with('outlet')
            ->whereHas('outlet', fn ($query) => $query->where('type', $outletType))
            ->orderBy('name')
            ->get(['id', 'name', 'outlet_id']);

        return view('modules.reports.outlet-item-sales-summary', compact(
            'report',
            'from',
            'to',
            'outletId',
            'categoryId',
            'outlets',
            'categories',
            'outletType',
        ));
    }

    private function outletTypeExportExcel(Request $request, string $outletType): StreamedResponse
    {
        $report   = $this->buildReport($request, $outletType)[4];
        $filename = sprintf('%s-item-sales-summary-%s-to-%s.xls', $outletType, $report['from'], $report['to']);

        return $this->streamExcelDownload($report, $filename);
    }

    private function outletTypeExportPdf(Request $request, string $outletType): Response
    {
        $report   = $this->buildReport($request, $outletType)[4];
        $filename = sprintf('%s-item-sales-summary-%s-to-%s.pdf', $outletType, $report['from'], $report['to']);

        return $this->downloadPdf($report, $filename);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: ?string, 3: ?string, 4: array} */
    private function buildReport(Request $request, ?string $outletType = null): array
    {
        $from       = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to         = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();
        $outletId   = $request->filled('outlet_id') ? (string) $request->input('outlet_id') : null;
        $categoryId = $request->filled('category_id') ? (string) $request->input('category_id') : null;

        return [
            $from,
            $to,
            $outletId,
            $categoryId,
            $this->reporting->menuItemSalesSummary($from, $to, $outletId, $categoryId, $outletType),
        ];
    }

    private function streamExcelDownload(array $report, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($report): void {
            echo $this->renderExcelTable($report);
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function downloadPdf(array $report, string $filename): Response
    {
        return Pdf::loadView('modules.reports.pdf.menu-item-sales-summary', compact('report'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function renderExcelTable(array $report): string
    {
        $fmt   = fn (float $v): string => number_format($v, 0, '.', ',');
        $title = $report['report_title'] ?? 'Menu Item Sales Summary';

        ob_start();

        echo '<table border="1">';
        echo '<tr><th colspan="8"><strong>'.e($title).'</strong></th></tr>';
        echo '<tr><td colspan="8">'.e($report['hotel_name']).'</td></tr>';
        echo '<tr><td colspan="8">Date: '.e($report['from']).' To '.e($report['to']).' | Outlet: '.e($report['outlet_name']).' | Category: '.e($report['category_filter']).'</td></tr>';
        echo '<tr><th>Menu item</th><th>Quantity</th><th>Price (avg)</th><th>Amount</th><th>Discount</th><th>Net rate</th><th>Tax</th><th>Total amount</th></tr>';

        foreach ($report['categories'] as $category) {
            echo '<tr><td colspan="8"><strong>'.e($category['name']).'</strong></td></tr>';

            foreach ($category['subcategories'] as $subcategory) {
                echo '<tr><td colspan="8"><em>'.e($subcategory['name']).'</em></td></tr>';

                foreach ($subcategory['items'] as $item) {
                    echo '<tr>';
                    echo '<td>'.e($item['name']).'</td>';
                    echo '<td>'.e($item['quantity']).'</td>';
                    echo '<td>'.e($fmt($item['price_avg'])).'</td>';
                    echo '<td>'.e($fmt($item['amount'])).'</td>';
                    echo '<td>'.e($fmt($item['discount'])).'</td>';
                    echo '<td>'.e($fmt($item['net_rate'])).'</td>';
                    echo '<td>'.e($fmt($item['tax'])).'</td>';
                    echo '<td>'.e($fmt($item['total_amount'])).'</td>';
                    echo '</tr>';
                }

                $sub = $subcategory['subtotal'];
                echo '<tr><td><strong>Subcategory sub total</strong></td>';
                echo '<td>'.e($sub['quantity']).'</td><td></td>';
                echo '<td>'.e($fmt($sub['amount'])).'</td>';
                echo '<td>'.e($fmt($sub['discount'])).'</td>';
                echo '<td>'.e($fmt($sub['net_rate'])).'</td>';
                echo '<td>'.e($fmt($sub['tax'])).'</td>';
                echo '<td>'.e($fmt($sub['total_amount'])).'</td></tr>';
            }

            $cat = $category['subtotal'];
            echo '<tr><td><strong>Category sub total</strong></td>';
            echo '<td>'.e($cat['quantity']).'</td><td></td>';
            echo '<td>'.e($fmt($cat['amount'])).'</td>';
            echo '<td>'.e($fmt($cat['discount'])).'</td>';
            echo '<td>'.e($fmt($cat['net_rate'])).'</td>';
            echo '<td>'.e($fmt($cat['tax'])).'</td>';
            echo '<td>'.e($fmt($cat['total_amount'])).'</td></tr>';
        }

        $grand = $report['grand_total'];
        echo '<tr><td><strong>Grand total</strong></td>';
        echo '<td>'.e($grand['quantity']).'</td><td></td>';
        echo '<td>'.e($fmt($grand['amount'])).'</td>';
        echo '<td>'.e($fmt($grand['discount'])).'</td>';
        echo '<td>'.e($fmt($grand['net_rate'])).'</td>';
        echo '<td>'.e($fmt($grand['tax'])).'</td>';
        echo '<td>'.e($fmt($grand['total_amount'])).'</td></tr>';
        echo '</table>';

        return (string) ob_get_clean();
    }
}
