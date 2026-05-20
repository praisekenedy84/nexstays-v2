<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Till\Models\TillSession;
use App\Domain\Till\Services\TillSessionService;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\Till\CloseTillSessionRequest;
use App\Http\Requests\Api\V1\Till\OpenTillSessionRequest;
use App\Http\Resources\Api\V1\TillSessionResource;
use Brick\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TillController extends Controller
{
    use RespondsWithJsonApi;

    public function __construct(
        private readonly TillSessionService $tillSessionService
    ) {}

    public function open(OpenTillSessionRequest $request): JsonResponse
    {
        $currency = $request->validated('currency', config('nexstay.currency.default', 'TZS'));
        $float = Money::of($request->validated('float_amount'), $currency);

        $session = $this->tillSessionService->open(
            $request->validated('outlet_id'),
            $float,
            $currency
        );

        return $this->respond(TillSessionResource::make($session), 201);
    }

    public function active(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('manage-till'), 403);

        $session = $this->tillSessionService->activeForOutlet($request->query('outlet_id'));

        return $this->respond($session ? TillSessionResource::make($session) : null);
    }

    public function close(CloseTillSessionRequest $request, TillSession $till): JsonResponse
    {
        $declared = Money::of($request->validated('declared_cash'), $till->currency);

        return $this->respond(TillSessionResource::make(
            $this->tillSessionService->close($till, $declared, $request->validated('manager_notes'))
        ));
    }

    public function history(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-till'), 403);

        $sessions = TillSession::query()
            ->with('outlet')
            ->when($request->filled('outlet_id'), fn ($q) => $q->where('outlet_id', $request->input('outlet_id')))
            ->latest('opened_at')
            ->paginate(min((int) $request->query('per_page', 20), 50));

        return $this->respondCollection(TillSessionResource::collection($sessions));
    }
}
