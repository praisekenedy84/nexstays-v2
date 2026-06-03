@php
    /** @var \App\Models\User $authUser */
    $authUser        = auth()->user();
    $userRole        = $authUser->roles->first();
    $permGroups      = \App\Http\Controllers\Web\RoleController::PERMISSION_GROUPS;
    $userPerms       = $authUser->getAllPermissions()->pluck('name')->flip()->all();
@endphp
<x-layouts.app active-nav="profile" title="My account" subtitle="Profile, password, and your access permissions">

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Left col: editable form --}}
        <div class="space-y-6 lg:col-span-2">
            <form method="POST" action="{{ route('tenant.profile.update') }}" class="card space-y-5 p-6">
                @csrf @method('PUT')

                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Account details</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">Full name</label>
                        <input name="name" value="{{ old('name', $user->name) }}" required class="input-field">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">Email address</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input-field">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <hr class="border-slate-100">

                <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Change password</h3>
                <p class="text-xs text-ink-muted">Current password is required to save any changes.</p>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Current password <span class="text-red-500">*</span></label>
                    <x-ui.password-input name="current_password" required autocomplete="current-password" class="input-field max-w-sm" />
                    @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">New password</label>
                        <x-ui.password-input name="password" autocomplete="new-password" placeholder="Leave blank to keep current" class="input-field" />
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">Confirm new password</label>
                        <x-ui.password-input name="password_confirmation" autocomplete="new-password" class="input-field" />
                    </div>
                </div>

                <div><button type="submit" class="btn-primary">Save changes</button></div>
            </form>
        </div>

        {{-- Right col: role + permissions (read-only) --}}
        <div class="space-y-4">
            <div class="card p-5">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">My role</p>
                @if ($userRole)
                    <span class="inline-block rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">
                        {{ str_replace('_', ' ', ucwords($userRole->name, '_')) }}
                    </span>
                @else
                    <p class="text-sm text-ink-muted">No role assigned.</p>
                @endif
            </div>

            <div class="card p-5">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">My permissions</p>
                @if (empty($userPerms))
                    <p class="text-sm text-ink-muted">No permissions assigned.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($permGroups as $groupLabel => $groupPerms)
                            @php $granted = array_filter($groupPerms, fn ($p) => isset($userPerms[$p])); @endphp
                            @if ($granted)
                                <div>
                                    <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $groupLabel }}</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($granted as $perm)
                                            <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">
                                                {{ str_replace('-', ' ', $perm) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>

</x-layouts.app>
