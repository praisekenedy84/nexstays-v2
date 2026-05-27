<x-platform.layout title="Hotels" activeNav="tenants">

    {{-- Provisioned credentials banner --}}
    @if (session('provisioned'))
        @php $p = session('provisioned'); @endphp
        <div class="mb-6 rounded-2xl bg-emerald-900/40 p-5 ring-1 ring-emerald-600/40">
            <p class="mb-3 text-sm font-semibold text-emerald-300">
                ✓ {{ $p['property_name'] }} provisioned — save these credentials now
            </p>
            <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-xs text-slateald-300 sm:grid-cols-4">
                <span class="text-slate-400">Property code</span>
                <code class="font-mono text-white">{{ $p['property_code'] }}</code>
                <span class="text-slate-400">Admin email</span>
                <code class="font-mono text-white">{{ $p['admin_email'] }}</code>
                <span class="text-slate-400">Password</span>
                <code class="font-mono text-white">{{ $p['password'] }}</code>
                <span class="text-slate-400">Login URL</span>
                <a href="{{ $p['login_url'] }}" class="font-mono text-indigo-400 hover:underline" target="_blank">{{ $p['login_url'] }}</a>
            </div>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4">
            <h2 class="text-sm font-medium text-slate-400">
                {{ Stancl\Tenancy\Database\Models\Tenant::count() }} hotel{{ Stancl\Tenancy\Database\Models\Tenant::count() !== 1 ? 's' : '' }} registered
            </h2>
            <x-ui.search-bar :value="$search" placeholder="Property code or name…" />
        </div>
        <a href="{{ route('platform.tenants.create') }}"
           class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
            + Provision hotel
        </a>
    </div>

    @if ($tenants->isEmpty() && ! $search)
        <div class="rounded-2xl border border-dashed border-white/10 py-16 text-center text-slate-500">
            No hotels yet. Provision your first one.
        </div>
    @else
        <div class="overflow-hidden rounded-2xl ring-1 ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-white/5 text-xs font-medium uppercase tracking-wider text-slate-400">
                    <tr>
                        <x-ui.sort-th column="id" label="Property code" :sort="$sort" :dir="$dir" />
                        <th class="px-6 py-3 text-left">Name</th>
                        <x-ui.sort-th column="created_at" label="Created" :sort="$sort" :dir="$dir" />
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($tenants as $tenant)
                        @php $suspended = ! empty($tenant->suspended_at); @endphp
                        <tr class="bg-slate-900/40 hover:bg-white/5 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-300">{{ $tenant->id }}</td>
                            <td class="px-6 py-4 font-medium text-white">{{ $tenant->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-400">{{ $tenant->created_at?->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                @if ($suspended)
                                    <span class="inline-flex rounded-full bg-red-900/50 px-2.5 py-0.5 text-xs font-medium text-red-300 ring-1 ring-red-700/50">Suspended</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-900/50 px-2.5 py-0.5 text-xs font-medium text-emerald-300 ring-1 ring-emerald-700/50">Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('platform.tenants.show', $tenant->id) }}"
                                       class="rounded-lg bg-slate-700/40 px-3 py-1 text-xs font-medium text-slate-300 hover:bg-slate-700/60 transition">
                                        View
                                    </a>
                                    @if ($suspended)
                                        <form method="POST" action="{{ route('platform.tenants.restore', $tenant->id) }}">
                                            @csrf @method('PATCH')
                                            <button class="rounded-lg bg-emerald-700/40 px-3 py-1 text-xs font-medium text-emerald-300 hover:bg-emerald-700/60 transition">
                                                Restore
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('platform.tenants.suspend', $tenant->id) }}">
                                            @csrf @method('PATCH')
                                            <button class="rounded-lg bg-yellow-700/30 px-3 py-1 text-xs font-medium text-yellow-300 hover:bg-yellow-700/50 transition">
                                                Suspend
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('platform.tenants.destroy', $tenant->id) }}"
                                          onsubmit="return confirm('Permanently delete {{ $tenant->id }} and all its data? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg bg-red-900/30 px-3 py-1 text-xs font-medium text-red-400 hover:bg-red-900/50 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">No hotels match your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-white/10 px-6 py-3">{{ $tenants->links() }}</div>
        </div>
    @endif

</x-platform.layout>
