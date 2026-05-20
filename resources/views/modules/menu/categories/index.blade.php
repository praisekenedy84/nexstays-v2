<x-layouts.app active-nav="menu-categories" title="Menu categories">
    @can('manage-menu')<div class="mb-4 flex justify-end"><a href="{{ route('tenant.menu-categories.create') }}" class="btn-primary">Add category</a></div>@endcan
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Outlet</th><th class="px-5 py-3">Order</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($categories as $cat)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $cat->name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $cat->outlet?->name }}</td>
                        <td class="px-5 py-4">{{ $cat->display_order }}</td>
                        <td class="px-5 py-4 text-right">
                            @can('manage-menu')
                                <a href="{{ route('tenant.menu-categories.edit', $cat) }}" class="text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.menu-categories.destroy', $cat) }}" class="ml-3 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
