<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Actions\PostAncillaryCharge;
use App\Domain\HBMS\Models\AncillaryService;
use App\Domain\HBMS\Models\Reservation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PostAncillaryChargeRequest;
use App\Http\Requests\Web\StoreAncillaryServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AncillaryServiceController extends Controller
{
    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['name', 'default_price', 'sort_order']) ? $request->query('sort') : 'sort_order';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $services = AncillaryService::query()
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->paginate(25)
            ->withQueryString();

        return view('modules.ancillary.index', compact('services', 'sort', 'dir', 'search'));
    }

    public function create(): View
    {
        return view('modules.ancillary.form', ['service' => new AncillaryService]);
    }

    public function store(StoreAncillaryServiceRequest $request): RedirectResponse
    {
        AncillaryService::query()->create([
            ...$request->validated(),
            'currency' => config('nexstay.currency.default', 'TZS'),
        ]);

        return redirect()
            ->route('tenant.ancillary-services.index')
            ->with('success', 'Service created.');
    }

    public function edit(AncillaryService $ancillaryService): View
    {
        return view('modules.ancillary.form', ['service' => $ancillaryService]);
    }

    public function update(StoreAncillaryServiceRequest $request, AncillaryService $ancillaryService): RedirectResponse
    {
        $ancillaryService->update($request->validated());

        return redirect()
            ->route('tenant.ancillary-services.index')
            ->with('success', 'Service updated.');
    }

    public function chargeForm(): View
    {
        return view('modules.ancillary.charge', [
            'services' => AncillaryService::query()->where('is_active', true)->orderBy('name')->get(),
            'reservations' => Reservation::query()
                ->with('guest')
                ->whereIn('status', ['checked_in', 'confirmed'])
                ->orderBy('check_in_date')
                ->get(),
        ]);
    }

    public function destroy(AncillaryService $ancillaryService): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-ancillary-services'), 403);

        $ancillaryService->delete();

        return redirect()
            ->route('tenant.ancillary-services.index')
            ->with('success', 'Service removed.');
    }

    public function charge(PostAncillaryChargeRequest $request): RedirectResponse
    {
        try {
            app(PostAncillaryCharge::class)->execute($request->validated());
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.ancillary-services.charge')
            ->with('success', 'Charge posted to guest folio.');
    }
}
