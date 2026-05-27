<x-layouts.app active-nav="room-types" title="Room types">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Room type name…" />
        @can('manage-room-types')
            <a href="{{ route('tenant.room-types.create') }}" class="btn-primary">Add room type</a>
        @endcan
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3 w-12"></th>
                    <x-ui.sort-th column="name" label="Name" :sort="$sort" :dir="$dir" />
                    <th class="px-5 py-3 text-left">Code</th>
                    <x-ui.sort-th column="base_rate" label="Base rate" :sort="$sort" :dir="$dir" />
                    <x-ui.sort-th column="rooms_count" label="Rooms" :sort="$sort" :dir="$dir" />
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($roomTypes as $type)
                    <tr>
                        <td class="pl-5 py-3">
                            @php $cover = \App\Domain\HBMS\Models\RoomType::photoUrl(($type->photos ?? [])[0] ?? null); @endphp
                            @if ($cover)
                                <img src="{{ $cover }}" alt="{{ $type->name }}"
                                     class="size-10 rounded-lg object-cover border border-slate-100">
                            @else
                                <div class="size-10 rounded-lg border border-dashed border-slate-200 bg-slate-50"></div>
                            @endif
                        </td>
                        <td class="px-5 py-4 font-medium">{{ $type->name }}</td>
                        <td class="px-5 py-4 font-mono text-xs">{{ $type->code }}</td>
                        <td class="px-5 py-4">@money($type->base_rate)</td>
                        <td class="px-5 py-4">{{ $type->rooms_count }}</td>
                        <td class="px-5 py-4 text-right">
                            @can('manage-room-types')
                                <a href="{{ route('tenant.room-types.edit', $type) }}" class="text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.room-types.destroy', $type) }}" class="ml-3 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-ink-muted">No room types found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $roomTypes->links() }}</div>
    </div>
</x-layouts.app>
