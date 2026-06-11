<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\FacilitySettingsService;
use App\Domain\Shared\Services\FbSettingsService;
use App\Domain\Shared\Services\PaymentMethodSettingsService;
use App\Domain\Shared\Services\TaxSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateFacilitySettingsRequest;
use App\Http\Requests\Web\UpdateFbSettingsRequest;
use App\Http\Requests\Web\UpdatePaymentMethodSettingsRequest;
use App\Http\Requests\Web\UpdateTaxSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FinanceSettingsController extends Controller
{
    public function __construct(
        private readonly TaxSettingsService $taxSettings,
        private readonly PaymentMethodSettingsService $paymentMethodSettings,
        private readonly FbSettingsService $fbSettings,
        private readonly FacilitySettingsService $facilitySettings,
    ) {}

    public function edit(): View
    {
        $settings = $this->taxSettings->all();

        // Convert stored decimals (0.18) to display percentages (18) for the form
        $settings['vat_rate_pct']              = round((float) $settings['vat_rate'] * 100, 4);
        $settings['rate_room_charge_pct']      = round((float) $settings['rates']['room_charge'] * 100, 4);
        $settings['rate_restaurant_pct']       = round((float) $settings['rates']['restaurant'] * 100, 4);
        $settings['rate_bar_pct']              = round((float) $settings['rates']['bar'] * 100, 4);

        return view('modules.finance.settings', compact('settings'));
    }

    public function update(UpdateTaxSettingsRequest $request): RedirectResponse
    {
        $v = $request->validated();

        // Form values are percentages — convert to decimals before storing
        $toDecimal = fn (mixed $pct): string => number_format((float) $pct / 100, 6, '.', '');

        $this->taxSettings->update([
            'tax_inclusive' => $v['tax_inclusive'] === '1',
            'vat_rate'      => $toDecimal($v['vat_rate']),
            'tax_code'      => $v['tax_code'],
            'rates'         => [
                'room_charge' => $toDecimal($v['rate_room_charge']),
                'restaurant'  => $toDecimal($v['rate_restaurant']),
                'bar'         => $toDecimal($v['rate_bar']),
            ],
        ]);

        return redirect()
            ->route('tenant.finance.settings.edit')
            ->with('success', 'Finance & tax settings updated.');
    }

    public function paymentMethodsEdit(): View
    {
        return view('modules.finance.payment-methods', [
            'settings' => $this->paymentMethodSettings->all(),
            'methods'  => PaymentMethodSettingsService::METHODS,
        ]);
    }

    public function paymentMethodsUpdate(UpdatePaymentMethodSettingsRequest $request): RedirectResponse
    {
        $this->paymentMethodSettings->update($request->validated('enabled', []));

        return redirect()
            ->route('tenant.finance.payment-methods.edit')
            ->with('success', 'Payment methods updated.');
    }

    public function fbSettingsEdit(): View
    {
        return view('modules.finance.fb-settings', [
            'settings' => $this->fbSettings->all(),
            'modes'    => FbSettingsService::SETTLEMENT_MODES,
        ]);
    }

    public function fbSettingsUpdate(UpdateFbSettingsRequest $request): RedirectResponse
    {
        $this->fbSettings->update($request->validated());

        return redirect()
            ->route('tenant.finance.fb-settings.edit')
            ->with('success', 'F&B settings updated.');
    }

    public function facilitySettingsEdit(): View
    {
        return view('modules.finance.facility-settings', [
            'settings' => $this->facilitySettings->all(),
        ]);
    }

    public function facilitySettingsUpdate(UpdateFacilitySettingsRequest $request): RedirectResponse
    {
        $this->facilitySettings->update($request->validated());

        return redirect()
            ->route('tenant.finance.facility-settings.edit')
            ->with('success', 'Facility fee settings updated.');
    }
}
