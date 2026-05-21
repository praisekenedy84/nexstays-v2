<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Actions\SendDailyReportEmail;
use App\Domain\Shared\Services\ReportDeliverySettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateReportDeliverySettingsRequest;
use Illuminate\Http\RedirectResponse;

class ReportDeliverySettingsController extends Controller
{
    public function __construct(
        private readonly ReportDeliverySettingsService $settingsService,
        private readonly SendDailyReportEmail $sendDailyReportEmail
    ) {}

    public function update(UpdateReportDeliverySettingsRequest $request): RedirectResponse
    {
        $this->settingsService->update([
            'recipient_email' => (string) $request->validated('recipient_email'),
            'send_time' => (string) $request->validated('send_time'),
            'timezone' => (string) $request->validated('timezone'),
        ]);

        return redirect()
            ->route('tenant.reports')
            ->with('success', 'Report email settings updated.');
    }

    public function sendNow(): RedirectResponse
    {
        if (! $this->sendDailyReportEmail->execute()) {
            return redirect()
                ->route('tenant.reports')
                ->with('error', 'Set a recipient email before sending reports.');
        }

        return redirect()
            ->route('tenant.reports')
            ->with('success', 'Report email sent successfully.');
    }
}
