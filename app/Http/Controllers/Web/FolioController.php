<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Folio;
use App\Domain\Shared\Services\FolioService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PostFolioPaymentRequest;
use Brick\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FolioController extends Controller
{
    public function postPayment(PostFolioPaymentRequest $request, Folio $folio): RedirectResponse
    {
        if ($folio->status !== 'open') {
            return back()->with('error', 'This folio is not open for payments.');
        }

        $folio->loadMissing('reservation');

        if ($folio->reservation?->hasPendingOverstay()) {
            return back()->withInput()->with(
                'error',
                'Settle the pending overstay charge (pay or waive) before recording other folio payments.'
            );
        }

        $folioService = app(FolioService::class);
        $amount       = Money::of($request->validated('amount'), $folio->currency);
        $balance      = $folioService->balance($folio);

        if ($amount->isGreaterThan($balance)) {
            return back()->withInput()->with('error', 'Payment amount exceeds the outstanding folio balance.');
        }

        try {
            $folioService->postPayment(
                folio: $folio,
                amount: $amount,
                method: $request->validated('method'),
                tillSessionId: null,
                notes: $request->validated('notes'),
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $this->redirectAfterFolioAction(
            $folio,
            'Payment of '.number_format($amount->getAmount()->toFloat()).' '.$folio->currency.' recorded.'
        );
    }

    public function writeOff(Request $request, Folio $folio): RedirectResponse
    {
        abort_unless(auth()->user()?->can('post-folio-charges'), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        if ($folio->reservation?->hasPendingOverstay()) {
            return back()->withInput()->with(
                'error',
                'Settle the pending overstay charge (pay or waive) before writing off other folio balances.'
            );
        }

        $amount = isset($validated['amount'])
            ? Money::of($validated['amount'], $folio->currency)
            : null;

        try {
            app(FolioService::class)->postWriteOff(
                folio: $folio,
                reason: $validated['reason'],
                amount: $amount,
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $this->redirectAfterFolioAction($folio, 'Folio balance written off.');
    }

    private function redirectAfterFolioAction(Folio $folio, string $message): RedirectResponse
    {
        $folio->loadMissing('reservation');
        $reservation = $folio->reservation;

        if ($reservation !== null) {
            return redirect()
                ->route('tenant.reservations.show', $reservation)
                ->with('success', $message);
        }

        return back()->with('success', $message);
    }
}
