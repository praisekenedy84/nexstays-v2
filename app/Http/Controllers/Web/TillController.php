<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\Outlet;
use App\Domain\Till\Models\TillSession;
use App\Domain\Till\Services\TillSessionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CloseTillSessionRequest;
use App\Http\Requests\Web\OpenTillSessionRequest;
use Brick\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TillController extends Controller
{
    public function __construct(
        private readonly TillSessionService $tillSessionService
    ) {}

    public function index(): View
    {
        $sessions = TillSession::query()
            ->with('outlet')
            ->latest('opened_at')
            ->paginate(15);

        $openSessions = TillSession::query()
            ->with('outlet')
            ->where('status', 'open')
            ->get();

        return view('modules.till.index', compact('sessions', 'openSessions'));
    }

    public function create(): View
    {
        return view('modules.till.create', [
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(OpenTillSessionRequest $request): RedirectResponse
    {
        $currency = config('nexstay.currency.default', 'TZS');

        try {
            $this->tillSessionService->open(
                $request->validated('outlet_id'),
                Money::of($request->validated('float_amount'), $currency),
                $currency
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.till.index')
            ->with('success', 'Till session opened.');
    }

    public function closeForm(TillSession $till): View
    {
        abort_unless($till->isOpen(), 403);

        $systemCash = $this->tillSessionService->systemCash($till);

        return view('modules.till.close', compact('till', 'systemCash'));
    }

    public function close(CloseTillSessionRequest $request, TillSession $till): RedirectResponse
    {
        try {
            $this->tillSessionService->close(
                $till,
                Money::of($request->validated('declared_cash'), $till->currency),
                $request->validated('manager_notes')
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.till.index')
            ->with('success', 'Till session closed.');
    }
}
