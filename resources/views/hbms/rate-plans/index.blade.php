<x-layouts.app active-nav="rate-plans" title="Rate plans">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Rate plan name…" />
        @can('manage-rate-plans')
            <a href="{{ route('tenant.rate-plans.create') }}" class="btn-primary">Add rate plan</a>
        @endcan
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <x-ui.sort-th column="name" label="Name" :sort="$sort" :dir="$dir" />
                    <th class="px-5 py-3 text-left">Code</th>
                    <th class="px-5 py-3 text-left">Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ratePlans as $plan)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $plan->name }}</td>
                        <td class="px-5 py-4 font-mono text-xs">{{ $plan->code }}</td>
                        <td class="px-5 py-4">{{ $plan->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-5 py-4 text-right">
                            @can('manage-rate-plans')
                                <a href="{{ route('tenant.rate-plans.edit', $plan) }}" class="text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.rate-plans.destroy', $plan) }}" class="ml-3 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-ink-muted">No rate plans found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $ratePlans->links() }}</div>
    </div>
</x-layouts.app>
