<x-layouts.app active-nav="roles" title="Roles & permissions" subtitle="Manage what each role can access">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <x-ui.search-bar :value="$search" placeholder="Role name…" />
        <div class="flex gap-2">
            <a href="{{ route('tenant.roles.matrix') }}" class="btn-outline">Permission matrix</a>
            <a href="{{ route('tenant.roles.create') }}" class="btn-primary">Add role</a>
        </div>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <x-ui.sort-th column="name" label="Role" :sort="$sort" :dir="$dir" />
                    <x-ui.sort-th column="permissions_count" label="Permissions" :sort="$sort" :dir="$dir" />
                    <x-ui.sort-th column="users_count" label="Staff" :sort="$sort" :dir="$dir" />
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($roles as $role)
                    @php $isSystem = in_array($role->name, $systemRoles, true); @endphp
                    <tr>
                        <td class="px-5 py-4">
                            <span class="font-medium">{{ str_replace('_', ' ', ucwords($role->name, '_')) }}</span>
                            @if ($isSystem)
                                <span class="ml-2 rounded bg-slate-100 px-1.5 py-0.5 text-xs text-ink-muted">system</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-ink-muted">{{ $role->permissions_count }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $role->users_count }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('tenant.roles.edit', $role) }}" class="text-primary hover:underline">Edit</a>
                            <form method="POST" action="{{ route('tenant.roles.clone', $role) }}" class="ml-3 inline"
                                  title="Create a new role with the same permissions">
                                @csrf
                                <button type="submit" class="text-ink-muted hover:text-primary hover:underline">Clone</button>
                            </form>
                            @if (! $isSystem)
                                <form method="POST" action="{{ route('tenant.roles.destroy', $role) }}"
                                    class="ml-3 inline"
                                    onsubmit="return confirm('Delete this role? Staff must be reassigned first.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-ink-muted">No roles found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $roles->links() }}</div>
    </div>
</x-layouts.app>
