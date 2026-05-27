<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreRoomTypeRequest;
use App\Http\Requests\Web\UpdateRoomTypeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RoomTypeController extends Controller
{
    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['name', 'base_rate', 'rooms_count']) ? $request->query('sort') : 'name';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $roomTypes = RoomType::query()
            ->withCount('rooms')
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('hbms.room-types.index', compact('roomTypes', 'sort', 'dir', 'search'));
    }

    public function create(): View
    {
        return view('hbms.room-types.form', ['roomType' => new RoomType]);
    }

    public function store(StoreRoomTypeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['photos'] = $this->storePhotos($request->file('photos', []));

        RoomType::query()->create($validated);

        return redirect()->route('tenant.room-types.index')->with('success', 'Room type created.');
    }

    public function edit(RoomType $roomType): View
    {
        return view('hbms.room-types.form', compact('roomType'));
    }

    public function update(UpdateRoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        $validated = $request->validated();
        $disk = RoomType::photosDisk();

        $existingPhotos = collect($roomType->photos ?? []);
        $removePhotos = collect($request->input('remove_photos', []))
            ->filter(fn ($path) => is_string($path) && $existingPhotos->contains($path))
            ->values();

        $removePhotos->each(fn (string $path) => Storage::disk($disk)->delete($path));

        $keptPhotos = $existingPhotos->reject(fn (string $path) => $removePhotos->contains($path))->values()->all();
        $uploadedPhotos = $this->storePhotos($request->file('photos', []));

        $validated['photos'] = array_values(array_merge($keptPhotos, $uploadedPhotos));

        $roomType->update($validated);

        return redirect()->route('tenant.room-types.index')->with('success', 'Room type updated.');
    }

    public function destroy(RoomType $roomType): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-room-types'), 403);
        abort_if($roomType->rooms()->exists(), 403, 'Remove or reassign rooms before deleting this type.');

        collect($roomType->photos ?? [])->each(
            fn (string $path) => Storage::disk(RoomType::photosDisk())->delete($path)
        );

        $roomType->delete();

        return redirect()->route('tenant.room-types.index')->with('success', 'Room type removed.');
    }

    /**
     * @param array<int, UploadedFile> $photos
     * @return array<int, string>
     */
    private function storePhotos(array $photos): array
    {
        $disk = RoomType::photosDisk();

        return collect($photos)
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file): string => $file->store('room-types/photos', $disk))
            ->values()
            ->all();
    }
}
