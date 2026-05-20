<x-layouts.app active-nav="guests" title="Guests" subtitle="Guest profiles — matches /api/v1/guests">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex max-w-md flex-1 gap-2">
            <input type="search" name="search" value="{{ $search }}" placeholder="Name, email, phone…" class="input-field flex-1">
            <button type="submit" class="btn-primary">Search</button>
        </form>
        @can('manage-guests')
            <a href="{{ route('tenant.guests.create') }}" class="btn-primary">Add guest</a>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/80 text-xs font-medium uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Phone</th>
                        <th class="px-5 py-3">Nationality</th>
                        <th class="px-5 py-3">VIP</th>
                        @can('manage-guests')
                            <th class="px-5 py-3 text-right">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($guests as $guest)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-4 font-medium text-ink">{{ $guest->first_name }} {{ $guest->last_name }}</td>
                            <td class="px-5 py-4 text-ink-muted">{{ $guest->email ?? '—' }}</td>
                            <td class="px-5 py-4 text-ink-muted">{{ $guest->phone ?? '—' }}</td>
                            <td class="px-5 py-4 text-ink-muted">{{ $guest->nationality ?? '—' }}</td>
                            <td class="px-5 py-4 text-ink-muted">{{ $guest->vip_level ?? '—' }}</td>
                            @can('manage-guests')
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('tenant.guests.edit', $guest) }}" class="text-primary hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('tenant.guests.destroy', $guest) }}" class="ml-3 inline" onsubmit="return confirm('Remove this guest?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-ink-muted">No guests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($guests->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $guests->links() }}</div>
        @endif
    </div>
</x-layouts.app>
