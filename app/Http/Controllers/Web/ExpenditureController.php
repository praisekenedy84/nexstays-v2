<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Expenditures\Models\Expenditure;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreExpenditureRequest;
use App\Http\Requests\Web\UpdateExpenditureRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenditureController extends Controller
{
    public function index(Request $request): View
    {
        $expenditures = Expenditure::query()
            ->with(['outlet', 'recorder'])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->input('to')))
            ->latest('expense_date')
            ->paginate(25)
            ->withQueryString();

        $total = Expenditure::query()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->input('to')))
            ->sum('amount');

        return view('modules.expenditures.index', compact('expenditures', 'total'));
    }

    public function create(): View
    {
        return view('modules.expenditures.form', [
            'expenditure' => new Expenditure(['expense_date' => now()->toDateString()]),
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreExpenditureRequest $request): RedirectResponse
    {
        Expenditure::query()->create([
            ...$request->validated(),
            'currency' => config('nexstay.currency.default', 'TZS'),
            'recorded_by' => Auth::id(),
        ]);

        return redirect()
            ->route('tenant.expenditures.index')
            ->with('success', 'Expenditure recorded.');
    }

    public function edit(Expenditure $expenditure): View
    {
        return view('modules.expenditures.form', [
            'expenditure' => $expenditure,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateExpenditureRequest $request, Expenditure $expenditure): RedirectResponse
    {
        $expenditure->update($request->validated());

        return redirect()
            ->route('tenant.expenditures.index')
            ->with('success', 'Expenditure updated.');
    }

    public function destroy(Expenditure $expenditure): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-expenditures'), 403);

        $expenditure->delete();

        return redirect()
            ->route('tenant.expenditures.index')
            ->with('success', 'Expenditure removed.');
    }
}
