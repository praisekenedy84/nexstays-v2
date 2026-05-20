<x-layouts.app active-nav="damage" title="Damage reports" subtitle="Room damage tracking">
    @can('manage-damage')
        <div class="mb-4 flex justify-end"><a href="{{ route('tenant.damages.create') }}" class="btn-primary">Report damage</a></div>
    @endcan
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr><th class="px-5 py-3">Room</th><th class="px-5 py-3">Description</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Est. cost</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($damages as $d)
                    <tr>
                        <td class="px-5 py-4 font-medium">Room {{ $d->room?->room_number }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ Str::limit($d->description, 60) }}</td>
                        <td class="px-5 py-4"><x-ui.status-badge :status="$d->status" /></td>
                        <td class="px-5 py-4 text-right">@if($d->estimated_cost)@money($d->estimated_cost)@else—@endif</td>
                        <td class="px-5 py-4 text-right">
                            @can('manage-damage')
                                <a href="{{ route('tenant.damages.edit', $d) }}" class="text-primary hover:underline">Edit</a>
                                @if ($d->status !== 'resolved')
                                    <form method="POST" action="{{ route('tenant.damages.resolve', $d) }}" class="ml-3 inline">@csrf<button class="text-primary hover:underline">Resolve</button></form>
                                @endif
                                <form method="POST" action="{{ route('tenant.damages.destroy', $d) }}" class="ml-3 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $damages->links() }}</div>
    </div>
</x-layouts.app>
