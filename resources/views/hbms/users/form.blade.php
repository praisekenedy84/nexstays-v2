@php $isEdit = $user->exists; @endphp
<x-layouts.app active-nav="users" :title="$isEdit ? 'Edit staff' : 'Add staff'">
    <div class="mb-6"><a href="{{ route('tenant.users.index') }}" class="text-sm text-primary hover:underline">← Staff</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.users.update', $user) : route('tenant.users.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label><input name="name" value="{{ old('name', $user->name) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input-field"></div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label>
            <x-ui.password-input name="password" @unless($isEdit) required @endunless class="input-field" />
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Confirm password</label>
            <x-ui.password-input name="password_confirmation" class="input-field" />
        </div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Role</label>
            <select name="role" required class="input-field">
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $user->roles->first()?->name) === $role)>{{ str_replace('_', ' ', ucwords($role, '_')) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
