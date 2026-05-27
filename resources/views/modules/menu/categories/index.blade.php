<x-layouts.app active-nav="menu-categories" title="Menu categories">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Category name…" />
        @can('manage-menu')
            <a href="{{ route('tenant.menu-categories.create') }}" class="btn-primary">Add category</a>
        @endcan
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <x-ui.sort-th column="name" label="Name" :sort="$sort" :dir="$dir" />
                    <th class="px-5 py-3 text-left">Outlet</th>
                    <x-ui.sort-th column="display_order" label="Order" :sort="$sort" :dir="$dir" />
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $cat)
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
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-ink-muted">No categories found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $categories->links() }}</div>
    </div>
</x-layouts.app>
