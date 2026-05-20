<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\ReportingService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DebtController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting
    ) {}

    public function index(): View
    {
        $debts = $this->reporting->outstandingDebts();

        return view('modules.debts.index', compact('debts'));
    }
}
