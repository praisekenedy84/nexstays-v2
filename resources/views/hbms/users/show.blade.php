<x-layouts.app active-nav="users" :title="$user->name" subtitle="Staff member detail">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('tenant.users.index') }}" class="text-sm text-primary hover:underline">← Staff</a>
        <div class="flex gap-2">
            <a href="{{ route('tenant.users.edit', $user) }}" class="btn-outline">Edit</a>
            @if ($user->id !== auth()->id())
                <form method="POST" action="{{ route('tenant.users.destroy', $user) }}"
                      onsubmit="return confirm('Remove {{ addslashes($user->name) }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-outline text-red-600 hover:border-red-200 hover:bg-red-50">Remove</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Identity card --}}
        <div class="card space-y-4 p-6 lg:col-span-1">
            <div class="flex items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-full bg-primary/10 text-xl font-bold text-primary">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="font-semibold text-ink">{{ $user->name }}</p>
                    <p class="text-sm text-ink-muted">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Role</dt>
                    <dd class="mt-0.5">
                        @if ($userRole)
                            <span class="inline-block rounded-full bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary">
                                {{ str_replace('_', ' ', ucwords($userRole->name, '_')) }}
                            </span>
                        @else
                            <span class="text-ink-muted">No role assigned</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Member since</dt>
                    <dd class="mt-0.5 text-ink">{{ $user->created_at->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-ink-muted">Total permissions</dt>
                    <dd class="mt-0.5 font-semibold text-ink">{{ count($userPermissions) }}</dd>
                </div>
            </dl>
        </div>

        {{-- Permissions breakdown --}}
        <div class="card p-6 lg:col-span-2">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-muted">Effective permissions</h2>

            @if (empty($userPermissions))
                <p class="text-sm text-ink-muted">This user has no permissions.</p>
            @else
                <div class="space-y-5">
                    @foreach ($permissionGroups as $groupLabel => $groupPerms)
                        @php
                            $granted = array_filter($groupPerms, fn ($p) => isset($userPermissions[$p]));
                            $denied  = array_filter($groupPerms, fn ($p) => ! isset($userPermissions[$p]));
                        @endphp
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $groupLabel }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($groupPerms as $perm)
                                    @if (isset($userPermissions[$perm]))
                                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            {{ str_replace('-', ' ', $perm) }}
                                        </span>
                                    @else
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-400 line-through">
                                            {{ str_replace('-', ' ', $perm) }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @if (! $loop->last)<hr class="border-slate-100">@endif
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</x-layouts.app>
