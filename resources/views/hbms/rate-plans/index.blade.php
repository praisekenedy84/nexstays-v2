<x-layouts.app active-nav="rate-plans" title="Rate plans">
    @can('manage-rate-plans')<div class="mb-4 flex justify-end"><a href="{{ route('tenant.rate-plans.create') }}" class="btn-primary">Add rate plan</a></div>@endcan
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Code</th><th class="px-5 py-3">Active</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($ratePlans as $plan)
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
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $ratePlans->links() }}</div>
    </div>
</x-layouts.app>
