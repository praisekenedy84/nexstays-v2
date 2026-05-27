<x-layouts.app active-nav="damage" title="Damage reports" subtitle="Room damage tracking">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Description…" />
        @can('manage-damage')
            <a href="{{ route('tenant.damages.create') }}" class="btn-primary">Report damage</a>
        @endcan
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        @foreach (request()->except(['status', 'page']) as $key => $val)
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
        @endforeach
        <select name="status" class="input-field w-auto" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach (['reported', 'in_progress', 'resolved'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3 text-left">Room</th>
                    <th class="px-5 py-3 text-left">Description</th>
                    <x-ui.sort-th column="status" label="Status" :sort="$sort" :dir="$dir" />
                    <x-ui.sort-th column="estimated_cost" label="Est. cost" :sort="$sort" :dir="$dir" />
                    <x-ui.sort-th column="created_at" label="Reported" :sort="$sort" :dir="$dir" />
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($damages as $d)
                    <tr>
                        <td class="px-5 py-4 font-medium">Room {{ $d->room?->room_number }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ Str::limit($d->description, 60) }}</td>
                        <td class="px-5 py-4"><x-ui.status-badge :status="$d->status" /></td>
                        <td class="px-5 py-4">@if($d->estimated_cost)@money($d->estimated_cost)@else—@endif</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $d->created_at->format('d M Y') }}</td>
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
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-ink-muted">No damage reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $damages->links() }}</div>
    </div>
</x-layouts.app>
