<x-layouts.app active-nav="outlets" title="Outlets" subtitle="Restaurant, bar & lounge">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Outlet name…" />
        @can('manage-outlets')
            <a href="{{ route('tenant.outlets.create') }}" class="btn-primary">Add outlet</a>
        @endcan
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <x-ui.sort-th column="name" label="Name" :sort="$sort" :dir="$dir" />
                    <x-ui.sort-th column="type" label="Type" :sort="$sort" :dir="$dir" />
                    <th class="px-5 py-3 text-left">Active</th>
                    <th class="px-5 py-3 text-left">Tables</th>
                    <th class="px-5 py-3 text-left">Module</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($outlets->loadCount('tables') as $outlet)
                    @php
                        $moduleRoute = match ($outlet->type) {
                            'bar'    => 'tenant.bar.index',
                            'lounge' => 'tenant.lounge.index',
                            default  => 'tenant.restaurant.index',
                        };
                    @endphp
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $outlet->name }}</td>
                        <td class="px-5 py-4 capitalize">{{ $outlet->type }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $outlet->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $outlet->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            @can('manage-outlets')
                                <a href="{{ route('tenant.outlets.tables.index', $outlet) }}"
                                   class="font-medium text-primary hover:underline">
                                    {{ $outlet->tables_count }} table{{ $outlet->tables_count !== 1 ? 's' : '' }}
                                </a>
                            @else
                                {{ $outlet->tables_count }} tables
                            @endcan
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route($moduleRoute) }}" class="text-primary hover:underline">Open →</a>
                        </td>
                        <td class="px-5 py-4 text-right">
                            @can('manage-outlets')
                                <a href="{{ route('tenant.outlets.tables.index', $outlet) }}"
                                   class="text-xs text-ink-muted hover:text-ink">Tables</a>
                                <span class="mx-1 text-slate-300">·</span>
                                <a href="{{ route('tenant.outlets.edit', $outlet) }}"
                                   class="text-xs text-primary hover:underline">Edit</a>
                                <form method="POST"
                                      action="{{ route('tenant.outlets.destroy', $outlet) }}"
                                      class="ml-2 inline"
                                      onsubmit="return confirm('Delete outlet?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-rose-500 hover:text-rose-700">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $outlets->links() }}</div>
    </div>
</x-layouts.app>
