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
}
