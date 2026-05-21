<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\ReportDeliverySettingsService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportDeliverySettingsService $settingsService
    ) {}

    public function __invoke(): View
    {
        return view('hbms.reports', [
            'deliverySettings' => $this->settingsService->all(),
        ]);
    }
}
