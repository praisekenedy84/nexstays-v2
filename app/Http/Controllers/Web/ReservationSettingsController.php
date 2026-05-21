<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Services\ReservationSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateReservationSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReservationSettingsController extends Controller
{
    public function __construct(
        private readonly ReservationSettingsService $settingsService
    ) {}

    public function edit(): View
    {
        $settings = $this->settingsService->all();

        return view('hbms.reservations.settings', compact('settings'));
    }

    public function update(UpdateReservationSettingsRequest $request): RedirectResponse
    {
        $policy = $request->validated('cancellation_policy');
        $refundPercentage = $policy === 'percentage_refund'
            ? (float) $request->validated('refund_percentage', 0)
            : 0.0;

        $this->settingsService->update([
            'payment_mode' => 'prepaid',
            'cancellation' => [
                'policy' => $policy,
                'refund_percentage' => $refundPercentage,
            ],
        ]);

        return redirect()
            ->route('tenant.reservations.settings.edit')
            ->with('success', 'Reservation settings updated.');
    }
}
