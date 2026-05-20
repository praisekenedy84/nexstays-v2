<x-layouts.app active-nav="users" title="Staff" subtitle="Property users and roles">
    <div class="mb-4 flex justify-end">
        <a href="{{ route('tenant.users.create') }}" class="btn-primary">Add staff</a>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Email</th><th class="px-5 py-3">Role</th><th class="px-5 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $user->name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $user->email }}</td>
                        <td class="px-5 py-4">{{ str_replace('_', ' ', $user->roles->first()?->name ?? '—') }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('tenant.users.edit', $user) }}" class="text-primary hover:underline">Edit</a>
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('tenant.users.destroy', $user) }}" class="ml-3 inline" onsubmit="return confirm('Remove this user?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $users->links() }}</div>
    </div>
</x-layouts.app>
