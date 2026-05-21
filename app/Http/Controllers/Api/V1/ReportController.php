<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Shared\Services\ReportingService;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use RespondsWithJsonApi;

    public function __construct(
        private readonly ReportingService $reporting
    ) {}

    public function debts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-debts'), 403);

        $rows = $this->reporting->outstandingDebts()->map(fn (array $row) => [
            'folio_id' => $row['folio']->id,
            'folio_number' => $row['folio']->folio_number,
            'booking_ref' => $row['reservation']?->booking_ref,
            'guest' => $row['reservation']?->guest
                ? trim($row['reservation']->guest->first_name.' '.$row['reservation']->guest->last_name)
                : null,
            'balance' => $row['balance']->getAmount()->__toString(),
            'currency' => $row['folio']->currency,
        ]);

        return $this->respondCollection($rows);
    }

    public function fbRevenue(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-fb-reports'), 403);

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        return $this->respond($this->reporting->fbRevenueSplit($from, $to));
    }

    public function roomReservations(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-reports') || $request->user()?->can('view-reservations'), 403);

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        return $this->respond($this->reporting->roomReservationFinance($from, $to));
    }

    public function roomPaymentsAccounting(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-reports') || $request->user()?->can('view-reservations'), 403);

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        $report = $this->reporting->roomPaymentsAccounting($from, $to);

        $rows = $report['rows']->map(fn (array $row) => [
            'reservation_id' => $row['reservation']->id,
            'booking_ref' => $row['reservation']->booking_ref,
            'status' => $row['reservation']->status,
            'guest_name' => $row['guest_name'],
            'room_number' => $row['room_number'],
            'stay_nights' => $row['stay_nights'],
            'room_revenue' => $row['room_revenue'],
            'folio_charges' => $row['folio_charges'],
            'payments_received' => $row['payments_received'],
            'outstanding_balance' => $row['outstanding_balance'],
        ])->values();

        return $this->respond([
            'from' => $report['from'],
            'to' => $report['to'],
            'totals' => $report['totals'],
            'rows' => $rows,
        ]);
    }
}
