<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\RatePlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreRatePlanRequest;
use App\Http\Requests\Web\UpdateRatePlanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RatePlanController extends Controller
{
    public function index(): View
    {
        $ratePlans = RatePlan::query()->orderBy('name')->paginate(20);

        return view('hbms.rate-plans.index', compact('ratePlans'));
    }

    public function create(): View
    {
        return view('hbms.rate-plans.form', ['ratePlan' => new RatePlan(['is_active' => true])]);
    }

    public function store(StoreRatePlanRequest $request): RedirectResponse
    {
        RatePlan::query()->create([
            ...$request->validated(),
            'currency' => config('nexstay.currency.default', 'TZS'),
        ]);

        return redirect()->route('tenant.rate-plans.index')->with('success', 'Rate plan created.');
    }

    public function edit(RatePlan $ratePlan): View
    {
        return view('hbms.rate-plans.form', compact('ratePlan'));
    }

    public function update(UpdateRatePlanRequest $request, RatePlan $ratePlan): RedirectResponse
    {
        $ratePlan->update($request->validated());

        return redirect()->route('tenant.rate-plans.index')->with('success', 'Rate plan updated.');
    }

    public function destroy(RatePlan $ratePlan): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-rate-plans'), 403);
        abort_if($ratePlan->reservations()->exists(), 403, 'Rate plan is linked to reservations.');

        $ratePlan->delete();

        return redirect()->route('tenant.rate-plans.index')->with('success', 'Rate plan removed.');
    }
}
