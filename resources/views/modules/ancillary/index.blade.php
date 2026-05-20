<x-layouts.app active-nav="ancillary" title="Extra services" subtitle="Ancillary catalog & folio posting">
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        @can('post-folio-charges')
            <a href="{{ route('tenant.ancillary-services.charge') }}" class="btn-outline">Post charge</a>
        @endcan
        @can('manage-ancillary-services')
            <a href="{{ route('tenant.ancillary-services.create') }}" class="btn-primary">Add service</a>
        @endcan
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr><th class="px-5 py-3">Service</th><th class="px-5 py-3 text-right">Price</th><th class="px-5 py-3">Active</th>@can('manage-ancillary-services')<th></th>@endcan</tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($services as $service)
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
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
