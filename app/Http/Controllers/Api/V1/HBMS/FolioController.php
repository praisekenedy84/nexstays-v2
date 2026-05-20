<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HBMS;

use App\Domain\HBMS\Models\Folio;
use App\Domain\Shared\Services\FolioService;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\HBMS\PostFolioChargeRequest;
use App\Http\Resources\Api\V1\FolioResource;
use App\Http\Resources\Api\V1\FolioTransactionResource;
use Brick\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolioController extends Controller
{
    use RespondsWithJsonApi;

    public function show(Request $request, Folio $folio): JsonResponse
    {
        abort_unless($request->user()?->can('view-folios'), 403);

        $folio->load(['transactions' => fn ($q) => $q->orderByDesc('posted_at'), 'reservation.guest']);

        $balance = app(FolioService::class)->balance($folio);

        return $this->respond(FolioResource::make($folio), 200, [
            'balance' => $balance->getAmount()->__toString(),
            'currency' => $folio->currency,
        ]);
    }

    public function postCharge(PostFolioChargeRequest $request, Folio $folio): JsonResponse
    {
        $transaction = app(FolioService::class)->postCharge(
            folio: $folio,
            type: $request->validated('type'),
            description: $request->validated('description'),
            amount: Money::of($request->validated('amount'), $folio->currency),
            meta: $request->validated('meta', []),
        );

        return $this->respond(FolioTransactionResource::make($transaction), 201);
    }
}
