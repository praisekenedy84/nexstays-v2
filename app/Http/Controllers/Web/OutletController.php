<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreOutletRequest;
use App\Http\Requests\Web\UpdateOutletRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutletController extends Controller
{
    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['name', 'type']) ? $request->query('sort') : 'name';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $outlets = Outlet::query()
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('modules.outlets.index', compact('outlets', 'sort', 'dir', 'search'));
    }

    public function create(): View
    {
        return view('modules.outlets.form', ['outlet' => new Outlet(['is_active' => true])]);
    }

    public function store(StoreOutletRequest $request): RedirectResponse
    {
        Outlet::query()->create($request->validated());

        return redirect()->route('tenant.outlets.index')->with('success', 'Outlet created.');
    }

    public function edit(Outlet $outlet): View
    {
        return view('modules.outlets.form', compact('outlet'));
    }

    public function update(UpdateOutletRequest $request, Outlet $outlet): RedirectResponse
    {
        $outlet->update($request->validated());

        return redirect()->route('tenant.outlets.index')->with('success', 'Outlet updated.');
    }

    public function destroy(Outlet $outlet): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-outlets'), 403);

        $outlet->delete();

        return redirect()->route('tenant.outlets.index')->with('success', 'Outlet removed.');
    }
}
