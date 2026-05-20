<x-layouts.app active-nav="outlets" title="Outlets" subtitle="Restaurant, bar & lounge">
    @can('manage-outlets')
        <div class="mb-4 flex justify-end"><a href="{{ route('tenant.outlets.create') }}" class="btn-primary">Add outlet</a></div>
    @endcan
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Active</th><th class="px-5 py-3">Module</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($outlets as $outlet)
                    @php
                        $moduleRoute = match ($outlet->type) {
                            'bar' => 'tenant.bar.index',
                            'lounge' => 'tenant.lounge.index',
                            default => 'tenant.restaurant.index',
                        };
                    @endphp
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $outlet->name }}</td>
                        <td class="px-5 py-4 capitalize">{{ $outlet->type }}</td>
                        <td class="px-5 py-4">{{ $outlet->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-5 py-4"><a href="{{ route($moduleRoute) }}" class="text-primary hover:underline">Open</a></td>
                        <td class="px-5 py-4 text-right">
                            @can('manage-outlets')
                                <a href="{{ route('tenant.outlets.edit', $outlet) }}" class="text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.outlets.destroy', $outlet) }}" class="ml-3 inline" onsubmit="return confirm('Delete outlet?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $outlets->links() }}</div>
    </div>
</x-layouts.app>
