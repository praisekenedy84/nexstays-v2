<x-layouts.app active-nav="roles" title="Permissions matrix" subtitle="Which roles have which permissions">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('tenant.roles.index') }}" class="text-sm text-primary hover:underline">← Roles & permissions</a>
        @can('manage-roles')
            <a href="{{ route('tenant.roles.create') }}" class="btn-primary">Add role</a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-2xl ring-1 ring-slate-200">
        <table class="min-w-max w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="sticky left-0 z-10 min-w-[200px] bg-slate-50 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        Permission
                    </th>
                    @foreach ($roles as $role)
                        <th class="px-4 py-3 text-center text-xs font-medium text-ink-muted">
                            <a href="{{ route('tenant.roles.edit', $role) }}"
                               class="group inline-block rounded-lg px-2 py-1 hover:bg-slate-100 transition">
                                <span class="block font-semibold text-ink group-hover:text-primary">
                                    {{ str_replace('_', ' ', ucwords($role->name, '_')) }}
                                </span>
                                @if (in_array($role->name, $systemRoles, true))
                                    <span class="mt-0.5 block text-[10px] text-ink-subtle">system</span>
                                @endif
                            </a>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($permissionGroups as $groupLabel => $groupPermissions)
                    {{-- Group header row --}}
                    <tr class="bg-slate-50/60">
                        <td colspan="{{ $roles->count() + 1 }}"
                            class="sticky left-0 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            {{ $groupLabel }}
                        </td>
                    </tr>
                    {{-- Permission rows --}}
                    @foreach ($groupPermissions as $permission)
                        <tr class="hover:bg-slate-50/40">
                            <td class="sticky left-0 bg-white px-5 py-3 text-sm text-ink hover:bg-slate-50/40">
                                {{ str_replace('-', ' ', $permission) }}
                            </td>
                            @foreach ($roles as $role)
                                <td class="px-4 py-3 text-center">
                                    @if (isset($rolePermissions[$role->id][$permission]))
                                        <svg class="mx-auto size-4 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <span class="block text-center text-slate-200">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
