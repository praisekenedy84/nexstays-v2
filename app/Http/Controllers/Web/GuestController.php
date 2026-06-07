<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Guest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HBMS\StoreGuestRequest;
use App\Http\Requests\Web\UpdateGuestRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['last_name', 'email', 'nationality', 'created_at']) ? $request->query('sort') : 'last_name';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $guests = Guest::query()
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")
                ->orWhere('phone', 'ilike', "%{$search}%")))
            ->orderBy($sort, $dir)
            ->paginate(25)
            ->withQueryString();

        return view('hbms.guests.index', compact('guests', 'search', 'sort', 'dir'));
    }

    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-guests') || $request->user()?->can('manage-guests'), 403);

        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $guests = Guest::query()
            ->where(fn ($inner) => $inner
                ->where('first_name', 'ilike', "%{$query}%")
                ->orWhere('last_name', 'ilike', "%{$query}%")
                ->orWhere('email', 'ilike', "%{$query}%")
                ->orWhere('phone', 'ilike', "%{$query}%"))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'phone']);

        return response()->json([
            'data' => $guests->map(fn (Guest $guest) => [
                'id' => $guest->id,
                'label' => trim("{$guest->last_name}, {$guest->first_name}"),
                'phone' => $guest->phone,
            ])->values()->all(),
        ]);
    }

    public function create(): View
    {
        return view('hbms.guests.form', ['guest' => new Guest]);
    }

    public function store(StoreGuestRequest $request): RedirectResponse
    {
        Guest::query()->create($request->validated());

        return redirect()
            ->route('tenant.guests.index')
            ->with('success', 'Guest created successfully.');
    }

    public function edit(Guest $guest): View
    {
        return view('hbms.guests.form', compact('guest'));
    }

    public function update(UpdateGuestRequest $request, Guest $guest): RedirectResponse
    {
        $guest->update($request->validated());

        return redirect()
            ->route('tenant.guests.index')
            ->with('success', 'Guest updated successfully.');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-guests'), 403);

        $guest->delete();

        return redirect()
            ->route('tenant.guests.index')
            ->with('success', 'Guest removed.');
    }
}
