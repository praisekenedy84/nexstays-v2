<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreRoomTypeRequest;
use App\Http\Requests\Web\UpdateRoomTypeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoomTypeController extends Controller
{
    public function index(): View
    {
        $roomTypes = RoomType::query()->withCount('rooms')->orderBy('name')->paginate(20);

        return view('hbms.room-types.index', compact('roomTypes'));
    }

    public function create(): View
    {
        return view('hbms.room-types.form', ['roomType' => new RoomType]);
    }

    public function store(StoreRoomTypeRequest $request): RedirectResponse
    {
        RoomType::query()->create($request->validated());

        return redirect()->route('tenant.room-types.index')->with('success', 'Room type created.');
    }

    public function edit(RoomType $roomType): View
    {
        return view('hbms.room-types.form', compact('roomType'));
    }

    public function update(UpdateRoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        $roomType->update($request->validated());

        return redirect()->route('tenant.room-types.index')->with('success', 'Room type updated.');
    }

    public function destroy(RoomType $roomType): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-room-types'), 403);
        abort_if($roomType->rooms()->exists(), 403, 'Remove or reassign rooms before deleting this type.');

        $roomType->delete();

        return redirect()->route('tenant.room-types.index')->with('success', 'Room type removed.');
    }
}
