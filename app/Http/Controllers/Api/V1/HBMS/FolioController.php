<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HBMS;

use App\Domain\HBMS\Models\Folio;
use App\Domain\Shared\Services\FolioService;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\HBMS\PostFolioChargeRequest;
use App\Http\Requests\Api\V1\HBMS\PostFolioPaymentRequest;
use App\Http\Requests\Api\V1\HBMS\PostFolioWriteOffRequest;
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

    public function postPayment(PostFolioPaymentRequest $request, Folio $folio): JsonResponse
    {
        $folio->loadMissing('reservation');

        throw_if(
            $folio->reservation?->hasPendingOverstay(),
            \DomainException::class,
            'Settle the pending overstay charge (pay or waive) before recording other folio payments.'
        );

        $result = app(FolioService::class)->postPayment(
            folio: $folio,
            amount: Money::of($request->validated('amount'), $folio->currency),
            method: $request->validated('method'),
            tillSessionId: $request->validated('till_session_id'),
            notes: $request->validated('notes'),
        );

        return $this->respond(FolioTransactionResource::make($result['transaction']), 201, [
            'payment_id' => $result['payment']->id,
        ]);
    }

    public function writeOff(PostFolioWriteOffRequest $request, Folio $folio): JsonResponse
    {
        $folio->loadMissing('reservation');

        throw_if(
            $folio->reservation?->hasPendingOverstay(),
            \DomainException::class,
            'Settle the pending overstay charge (pay or waive) before writing off other folio balances.'
        );

        $amount = $request->validated('amount')
            ? Money::of($request->validated('amount'), $folio->currency)
            : null;

        $result = app(FolioService::class)->postWriteOff(
            folio: $folio,
            reason: $request->validated('reason'),
            amount: $amount,
        );

        return $this->respond(FolioTransactionResource::make($result['transaction']), 201, [
            'payment_id' => $result['payment']->id,
        ]);
    }
}
