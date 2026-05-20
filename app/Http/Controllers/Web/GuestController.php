<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Guest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HBMS\StoreGuestRequest;
use App\Http\Requests\Web\UpdateGuestRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $guests = Guest::query()
            ->when($search, function ($q) use ($search) {
                $term = '%'.mb_strtolower($search).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(first_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$term]);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        return view('hbms.guests.index', compact('guests', 'search'));
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
