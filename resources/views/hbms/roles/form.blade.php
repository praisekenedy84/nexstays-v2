@php
    $isEdit = $role->exists;
    $isSystem = $isEdit && in_array($role->name, $systemRoles, true);
@endphp
<x-layouts.app active-nav="roles" :title="$isEdit ? 'Edit role' : 'Add role'">
    <div class="mb-6">
        <a href="{{ route('tenant.roles.index') }}" class="text-sm text-primary hover:underline">← Roles & permissions</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('tenant.roles.update', $role) : route('tenant.roles.store') }}"
        class="max-w-2xl space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        @if (! $isEdit)
            <div class="card p-6">
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Role name</label>
                <input name="name" value="{{ old('name') }}" required placeholder="e.g. night_auditor"
                    class="input-field max-w-xs"
                    pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-ink-muted">Lowercase letters, numbers, and underscores.</p>
            </div>
        @else
            <div class="card p-6">
                <p class="text-sm font-medium">{{ str_replace('_', ' ', ucwords($role->name, '_')) }}</p>
                @if ($isSystem)
                    <p class="mt-1 text-xs text-ink-muted">
                        System role — permissions can be edited but this role cannot be deleted.
                    </p>
                @endif
            </div>
        @endif

        <div class="card p-6">
            <h2 class="mb-4 text-sm font-semibold text-ink">Permissions</h2>
            <div class="space-y-5">
                @foreach ($permissionGroups as $groupLabel => $groupPermissions)
                    <fieldset>
                        <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            {{ $groupLabel }}
                        </legend>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 sm:grid-cols-3">
                            @foreach ($groupPermissions as $permission)
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                        class="size-4 rounded border-slate-300 text-primary focus:ring-primary"
                                        @checked(in_array($permission, old('permissions', $rolePermissions), true))>
                                    <span>{{ str_replace('-', ' ', $permission) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    @if (! $loop->last)
                        <hr class="border-slate-100">
                    @endif
                @endforeach
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Save</button>
            <a href="{{ route('tenant.roles.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.app>
