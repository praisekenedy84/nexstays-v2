@php
    $isEdit = $reservation->exists;
@endphp

<x-layouts.app active-nav="reservations" :title="$isEdit ? 'Edit reservation' : 'New reservation'">
    <div class="mb-6">
        <a href="{{ route('tenant.reservations.index') }}" class="text-sm text-primary hover:underline">← Back to reservations</a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('tenant.reservations.update', $reservation) : route('tenant.reservations.store') }}" class="card max-w-3xl space-y-4 p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        @unless ($isEdit)
            <div>
                <label for="guest_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Guest</label>
                <select id="guest_id" name="guest_id" required class="input-field">
                    <option value="">Select guest…</option>
                    @foreach ($guests as $guest)
                        <option value="{{ $guest->id }}" @selected(old('guest_id', $reservation->guest_id) === $guest->id)>
                            {{ $guest->last_name }}, {{ $guest->first_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="room_type_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Room type</label>
                    <select id="room_type_id" name="room_type_id" required class="input-field">
                        @foreach ($roomTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('room_type_id', $reservation->room_type_id) === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="room_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Room (optional)</label>
                    <select id="room_id" name="room_id" class="input-field">
                        <option value="">Assign later</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id', $reservation->room_id) === $room->id)>
                                {{ $room->room_number }} — {{ $room->roomType?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="rate_plan_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Rate plan</label>
                <select id="rate_plan_id" name="rate_plan_id" class="input-field">
                    <option value="">Default</option>
                    @foreach ($ratePlans as $plan)
                        <option value="{{ $plan->id }}" @selected(old('rate_plan_id', $reservation->rate_plan_id) === $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="status" class="mb-1.5 block text-xs font-medium text-ink-muted">Status</label>
                    <select id="status" name="status" class="input-field">
                        <option value="confirmed" @selected(old('status', $reservation->status) === 'confirmed')>Confirmed</option>
                        <option value="inquiry" @selected(old('status', $reservation->status) === 'inquiry')>Inquiry</option>
                    </select>
                </div>
                <div>
                    <label for="source" class="mb-1.5 block text-xs font-medium text-ink-muted">Source</label>
                    <input id="source" name="source" value="{{ old('source', $reservation->source ?? 'direct') }}" class="input-field">
                </div>
            </div>
        @else
            <p class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-ink-muted">
                Booking <strong class="font-mono text-primary">{{ $reservation->booking_ref }}</strong>
                · {{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}
            </p>
        @endunless

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="check_in_date" class="mb-1.5 block text-xs font-medium text-ink-muted">Check-in</label>
                <input id="check_in_date" type="date" name="check_in_date" value="{{ old('check_in_date', $reservation->check_in_date?->format('Y-m-d')) }}" required class="input-field">
            </div>
            <div>
                <label for="check_out_date" class="mb-1.5 block text-xs font-medium text-ink-muted">Check-out</label>
                <input id="check_out_date" type="date" name="check_out_date" value="{{ old('check_out_date', $reservation->check_out_date?->format('Y-m-d')) }}" required class="input-field">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="adults" class="mb-1.5 block text-xs font-medium text-ink-muted">Adults</label>
                <input id="adults" type="number" min="1" max="10" name="adults" value="{{ old('adults', $reservation->adults ?? 2) }}" required class="input-field">
            </div>
            <div>
                <label for="children" class="mb-1.5 block text-xs font-medium text-ink-muted">Children</label>
                <input id="children" type="number" min="0" max="10" name="children" value="{{ old('children', $reservation->children ?? 0) }}" class="input-field">
            </div>
            <div>
                <label for="daily_rate" class="mb-1.5 block text-xs font-medium text-ink-muted">Daily rate (TZS)</label>
                <input id="daily_rate" type="number" step="0.01" min="0" name="daily_rate" value="{{ old('daily_rate', $reservation->daily_rate) }}" class="input-field">
            </div>
        </div>

        @if ($isEdit)
            <div>
                <label for="room_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Room</label>
                <select id="room_id" name="room_id" class="input-field">
                    <option value="">Unassigned</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(old('room_id', $reservation->room_id) === $room->id)>
                            {{ $room->room_number }} — {{ $room->roomType?->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label for="special_requests" class="mb-1.5 block text-xs font-medium text-ink-muted">Special requests</label>
            <textarea id="special_requests" name="special_requests" rows="3" class="input-field">{{ old('special_requests', $reservation->special_requests) }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Save changes' : 'Create reservation' }}</button>
            @if ($isEdit)
                <a href="{{ route('tenant.reservations.show', $reservation) }}" class="btn-secondary">Cancel</a>
            @else
                <a href="{{ route('tenant.reservations.index') }}" class="btn-secondary">Cancel</a>
            @endif
        </div>
    </form>
</x-layouts.app>
