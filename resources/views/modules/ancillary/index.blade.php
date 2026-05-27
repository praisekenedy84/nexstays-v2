<x-layouts.app active-nav="ancillary" title="Extra services" subtitle="Ancillary catalog & folio posting">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Service name…" />
        <div class="flex flex-wrap gap-2">
            @can('post-folio-charges')
                <a href="{{ route('tenant.ancillary-services.charge') }}" class="btn-outline">Post charge</a>
            @endcan
            @can('manage-ancillary-services')
                <a href="{{ route('tenant.ancillary-services.create') }}" class="btn-primary">Add service</a>
            @endcan
        </div>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <x-ui.sort-th column="name" label="Service" :sort="$sort" :dir="$dir" />
                    <x-ui.sort-th column="default_price" label="Price" :sort="$sort" :dir="$dir" class="text-right" />
                    <th class="px-5 py-3 text-left">Active</th>
                    @can('manage-ancillary-services')<th></th>@endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($services as $service)
                    <tr>
                        <td class="px-5 py-4"><span class="font-medium">{{ $service->name }}</span>@if($service->description)<br><span class="text-xs text-ink-muted">{{ $service->description }}</span>@endif</td>
                        <td class="px-5 py-4 text-right">@money($service->default_price)</td>
                        <td class="px-5 py-4">{{ $service->is_active ? 'Yes' : 'No' }}</td>
                        @can('manage-ancillary-services')
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('tenant.ancillary-services.edit', $service) }}" class="text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.ancillary-services.destroy', $service) }}" class="ml-3 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-ink-muted">No services found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $services->links() }}</div>
    </div>
</x-layouts.app>
