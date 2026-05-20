<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\ReportingService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TimeLeftController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting
    ) {}

    public function index(): View
    {
        $stays = $this->reporting->checkoutCountdown();

        return view('modules.time-left.index', compact('stays'));
    }
}
