@php $isEdit = $user->exists; @endphp
<x-layouts.app active-nav="users" :title="$isEdit ? 'Edit staff' : 'Add staff'">
    <div class="mb-6"><a href="{{ route('tenant.users.index') }}" class="text-sm text-primary hover:underline">← Staff</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.users.update', $user) : route('tenant.users.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label>
            <input name="name" value="{{ old('name', $user->name) }}" required class="input-field">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Username</label>
            <input name="username" value="{{ old('username', $user->username) }}" required autocomplete="username"
                pattern="[a-z0-9_.-]+" spellcheck="false" class="input-field">
            <p class="mt-1 text-xs text-ink-muted">Lowercase letters, numbers, dots, hyphens, and underscores.</p>
            @error('username')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Email <span class="text-ink-subtle">(optional)</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" class="input-field">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label>
            <x-ui.password-input
                name="password"
                autocomplete="new-password"
                :required="! $isEdit"
                class="input-field"
            />
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Confirm password</label>
            <x-ui.password-input
                name="password_confirmation"
                autocomplete="new-password"
                :required="! $isEdit"
                class="input-field"
            />
            @error('password_confirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Role</label>
            <select name="role" required class="input-field">
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $user->roles->first()?->name) === $role)>{{ str_replace('_', ' ', ucwords($role, '_')) }}</option>
                @endforeach
            </select>
            @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
