@php
    $isEdit = $guest->exists;
@endphp

<x-layouts.app active-nav="guests" :title="$isEdit ? 'Edit guest' : 'New guest'">
    <div class="mb-6">
        <a href="{{ route('tenant.guests.index') }}" class="text-sm text-primary hover:underline">← Back to guests</a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('tenant.guests.update', $guest) : route('tenant.guests.store') }}" class="card max-w-2xl space-y-4 p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="mb-1.5 block text-xs font-medium text-ink-muted">First name</label>
                <input id="first_name" name="first_name" value="{{ old('first_name', $guest->first_name) }}" required class="input-field">
            </div>
            <div>
                <label for="last_name" class="mb-1.5 block text-xs font-medium text-ink-muted">Last name</label>
                <input id="last_name" name="last_name" value="{{ old('last_name', $guest->last_name) }}" required class="input-field">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="mb-1.5 block text-xs font-medium text-ink-muted">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $guest->email) }}" class="input-field">
            </div>
            <div>
                <label for="phone" class="mb-1.5 block text-xs font-medium text-ink-muted">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone', $guest->phone) }}" class="input-field">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="nationality" class="mb-1.5 block text-xs font-medium text-ink-muted">Nationality (ISO)</label>
                <input id="nationality" name="nationality" maxlength="2" value="{{ old('nationality', $guest->nationality) }}" class="input-field uppercase">
            </div>
            <div>
                <label for="id_type" class="mb-1.5 block text-xs font-medium text-ink-muted">ID type</label>
                <input id="id_type" name="id_type" value="{{ old('id_type', $guest->id_type) }}" class="input-field">
            </div>
            <div>
                <label for="vip_level" class="mb-1.5 block text-xs font-medium text-ink-muted">VIP level (0–5)</label>
                <input id="vip_level" type="number" min="0" max="5" name="vip_level" value="{{ old('vip_level', $guest->vip_level ?? 0) }}" class="input-field">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="id_number" class="mb-1.5 block text-xs font-medium text-ink-muted">ID number</label>
                <input id="id_number" name="id_number" value="{{ old('id_number', $guest->id_number) }}" class="input-field">
            </div>
            <div>
                <label for="date_of_birth" class="mb-1.5 block text-xs font-medium text-ink-muted">Date of birth</label>
                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $guest->date_of_birth?->format('Y-m-d')) }}" class="input-field">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Save changes' : 'Create guest' }}</button>
            <a href="{{ route('tenant.guests.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.app>
