<x-platform.layout :title="($tenant->name ?? $tenant->id) . ' — Hotel detail'" activeNav="tenants">

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('platform.tenants.index') }}" class="text-sm text-slate-400 hover:text-white transition">← Hotels</a>
        <span class="text-slate-700">/</span>
        <span class="text-sm text-slate-300">{{ $tenant->name ?? $tenant->id }}</span>
    </div>

    {{-- Success flash --}}
    @if (session('success'))
        <div class="mb-6 rounded-2xl bg-emerald-900/40 p-4 ring-1 ring-emerald-600/40 text-sm text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Password reset result --}}
    @if (session('password_reset'))
        @php $pr = session('password_reset'); @endphp
        <div class="mb-6 rounded-2xl bg-amber-900/40 p-4 ring-1 ring-amber-600/40">
            <p class="mb-2 text-sm font-semibold text-amber-300">Password reset for {{ $pr['user_name'] }}</p>
            <div class="flex gap-6 text-xs">
                <span class="text-slate-400">Email: <code class="font-mono text-white">{{ $pr['email'] }}</code></span>
                <span class="text-slate-400">New password: <code class="font-mono text-white">{{ $pr['password'] }}</code></span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-white">{{ $tenant->name ?? '—' }}</h2>
                @if (! empty($tenant->suspended_at))
                    <span class="rounded-full bg-red-900/50 px-2.5 py-0.5 text-xs font-medium text-red-300 ring-1 ring-red-700/50">Suspended</span>
                @else
                    <span class="rounded-full bg-emerald-900/50 px-2.5 py-0.5 text-xs font-medium text-emerald-300 ring-1 ring-emerald-700/50">Active</span>
                @endif
            </div>
            <p class="mt-1 font-mono text-sm text-slate-400">{{ $tenant->id }} &nbsp;·&nbsp; Provisioned {{ $tenant->created_at?->format('d M Y') }}</p>
        </div>

        {{-- Maintenance actions --}}
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('platform.tenants.run-migrations', $tenant->id) }}">
                @csrf @method('PATCH')
                <button class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-slate-300 hover:bg-white/10 transition">
                    Run migrations
                </button>
            </form>
            <form method="POST" action="{{ route('platform.tenants.reseed-roles', $tenant->id) }}">
                @csrf @method('PATCH')
                <button class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-slate-300 hover:bg-white/10 transition">
                    Reseed roles
                </button>
            </form>
            @if (empty($tenant->suspended_at))
                <form method="POST" action="{{ route('platform.tenants.suspend', $tenant->id) }}">
                    @csrf @method('PATCH')
                    <button class="rounded-xl bg-yellow-700/30 px-3 py-2 text-xs font-medium text-yellow-300 hover:bg-yellow-700/50 transition">
                        Suspend hotel
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('platform.tenants.restore', $tenant->id) }}">
                    @csrf @method('PATCH')
                    <button class="rounded-xl bg-emerald-700/30 px-3 py-2 text-xs font-medium text-emerald-300 hover:bg-emerald-700/50 transition">
                        Restore hotel
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('platform.tenants.destroy', $tenant->id) }}"
                  onsubmit="return confirm('Permanently delete {{ $tenant->id }} and ALL its data?')">
                @csrf @method('DELETE')
                <button class="rounded-xl bg-red-900/30 px-3 py-2 text-xs font-medium text-red-400 hover:bg-red-900/50 transition">
                    Delete hotel
                </button>
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="mb-8 grid grid-cols-3 gap-4">
        @foreach ([['Staff', $stats['users']], ['Rooms', $stats['rooms']], ['Reservations', $stats['reservations']]] as [$label, $value])
            <div class="rounded-2xl bg-slate-800/60 p-5 ring-1 ring-white/5">
                <p class="text-xs font-medium text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold text-white">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Hotel settings --}}
    <div class="mb-8">
        <h3 class="mb-3 text-sm font-semibold text-slate-300">Settings</h3>
        <div class="rounded-2xl bg-slate-800/60 p-5 ring-1 ring-white/5">
            <form method="POST" action="{{ route('platform.tenants.settings', $tenant->id) }}" class="flex flex-wrap items-end gap-4">
                @csrf @method('PATCH')
                <div class="min-w-0 flex-1">
                    <label for="sms_sender_name" class="mb-1.5 block text-xs font-medium text-slate-400">
                        SMS sender name
                        <span class="ml-1 font-normal text-slate-500">— shown as "From" on guest's phone (max 11 chars, letters &amp; numbers only)</span>
                    </label>
                    <input id="sms_sender_name" type="text" name="sms_sender_name"
                        value="{{ old('sms_sender_name', $tenant->sms_sender_name ?? '') }}"
                        maxlength="11" placeholder="e.g. GrandHotel"
                        class="w-full max-w-xs rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    @error('sms_sender_name')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                    class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                    Save
                </button>
            </form>
        </div>
    </div>

    {{-- Staff / users --}}
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-300">Staff accounts</h3>
    </div>

    @if ($users->isEmpty())
        <div class="rounded-2xl border border-dashed border-white/10 py-10 text-center text-slate-500 text-sm">
            No users found in this tenant.
        </div>
    @else
        <div class="overflow-hidden rounded-2xl ring-1 ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-white/5 text-xs font-medium uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-3 text-left">Name</th>
                        <th class="px-5 py-3 text-left">Email</th>
                        <th class="px-5 py-3 text-left">Roles</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach ($users as $user)
                        <tr class="bg-slate-900/40 hover:bg-white/5 transition">
                            <td class="px-5 py-3 font-medium text-white">{{ $user->name }}</td>
                            <td class="px-5 py-3 text-slate-400">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                <form method="POST"
                                      action="{{ route('platform.tenants.users.change-role', [$tenant->id, $user->id]) }}">
                                    @csrf @method('PATCH')
                                    <div class="flex items-center gap-2">
                                        <select name="role"
                                                class="rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-xs text-slate-300 focus:border-indigo-500 focus:outline-none">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role }}"
                                                    @selected($user->roles->first()?->name === $role)>
                                                    {{ str_replace('_', ' ', $role) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                                class="rounded-lg bg-white/5 px-2 py-1 text-xs text-slate-400 hover:bg-white/10 hover:text-white transition">
                                            Save
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (empty($tenant->suspended_at))
                                        <form method="POST"
                                              action="{{ route('platform.impersonate.start', [$tenant->id, $user->id]) }}">
                                            @csrf
                                            <button class="rounded-lg bg-indigo-700/40 px-3 py-1 text-xs font-medium text-indigo-300 hover:bg-indigo-700/60 transition">
                                                Impersonate
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST"
                                          action="{{ route('platform.tenants.users.reset-password', [$tenant->id, $user->id]) }}"
                                          onsubmit="return confirm('Reset password for {{ addslashes($user->name) }}?')">
                                        @csrf
                                        <button class="rounded-lg bg-slate-700/40 px-3 py-1 text-xs font-medium text-slate-300 hover:bg-slate-700/60 transition">
                                            Reset password
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-platform.layout>
