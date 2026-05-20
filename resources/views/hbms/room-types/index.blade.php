<x-layouts.app active-nav="room-types" title="Room types">
    @can('manage-room-types')
        <div class="mb-4 flex justify-end"><a href="{{ route('tenant.room-types.create') }}" class="btn-primary">Add room type</a></div>
    @endcan
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Code</th><th class="px-5 py-3">Base rate</th><th class="px-5 py-3">Rooms</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($roomTypes as $type)
                    <tr>
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
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $roomTypes->links() }}</div>
    </div>
</x-layouts.app>
