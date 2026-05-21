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

        <div>
            <label for="room_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Room</label>
            <select
                id="room_id"
                name="room_id"
                required
                class="input-field"
                data-available-rooms-url="{{ route('tenant.reservations.available-rooms') }}"
                data-exclude-reservation-id="{{ $reservation->id }}"
            >
                <option value="">Select room…</option>
                @foreach ($rooms as $room)
                    <option
                        value="{{ $room->id }}"
                        data-daily-rate="{{ $room->daily_rate ?? 0 }}"
                        data-room-number="{{ $room->room_number }}"
                        data-room-type="{{ $room->roomType?->name }}"
                        data-status="{{ $room->status }}"
                        @selected(old('room_id', $reservation->room_id) === $room->id)
                    >
                        {{ $room->room_number }} — {{ $room->roomType?->name }} ({{ ucfirst(str_replace('_', ' ', $room->status)) }} · @money($room->daily_rate ?? 0))
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-subtle">
                Room options refresh automatically based on selected stay dates.
            </p>
            <p id="room_rate_hint" class="mt-1 text-xs text-ink-subtle"></p>
        </div>

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

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="adults" class="mb-1.5 block text-xs font-medium text-ink-muted">Adults</label>
                <input id="adults" type="number" min="1" max="10" name="adults" value="{{ old('adults', $reservation->adults ?? 2) }}" required class="input-field">
            </div>
            <div>
                <label for="children" class="mb-1.5 block text-xs font-medium text-ink-muted">Children</label>
                <input id="children" type="number" min="0" max="10" name="children" value="{{ old('children', $reservation->children ?? 0) }}" class="input-field">
            </div>
        </div>

        <p id="estimated_total" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-ink-muted"></p>

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

<script>
    (() => {
        const room = document.getElementById('room_id');
        const checkIn = document.getElementById('check_in_date');
        const checkOut = document.getElementById('check_out_date');
        const rateHint = document.getElementById('room_rate_hint');
        const estimatedTotal = document.getElementById('estimated_total');
        const availabilityEndpoint = room?.dataset.availableRoomsUrl;
        const excludeReservationId = room?.dataset.excludeReservationId;
        let latestRequestId = 0;

        if (!room || !checkIn || !checkOut || !estimatedTotal || !availabilityEndpoint) {
            return;
        }

        const formatMoney = (value) =>
            new Intl.NumberFormat('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);

        const recalculate = () => {
            const selectedOption = room.options[room.selectedIndex];
            const rate = Number(selectedOption?.dataset.dailyRate ?? 0);
            const inDate = checkIn.value ? new Date(checkIn.value + 'T00:00:00') : null;
            const outDate = checkOut.value ? new Date(checkOut.value + 'T00:00:00') : null;
            const nights = inDate && outDate ? Math.max(0, Math.round((outDate - inDate) / 86400000)) : 0;
            const total = nights * rate;

            if (selectedOption?.value) {
                rateHint.textContent = `Daily rate from room: TZS ${formatMoney(rate)}.`;
            } else {
                rateHint.textContent = '';
            }

            if (nights > 0 && selectedOption?.value) {
                estimatedTotal.textContent = `${nights} night(s) x TZS ${formatMoney(rate)} = TZS ${formatMoney(total)} estimated stay total.`;
            } else {
                estimatedTotal.textContent = 'Select room and stay dates to preview total stay amount.';
            }
        };

        const renderOptionLabel = (item) => {
            const statusLabel = String(item.status || '').replaceAll('_', ' ');
            const rate = Number(item.daily_rate ?? 0);
            const reason = item.reason === 'reserved_for_selected_dates'
                ? 'reserved for selected dates'
                : (item.reason === 'room_status_unavailable' ? 'not bookable by room status' : null);

            return `${item.room_number} — ${item.room_type_name ?? 'Room'} (${statusLabel} · TZS ${formatMoney(rate)})${reason ? ` — ${reason}` : ''}`;
        };

        const isDateRangeValid = () => {
            if (!checkIn.value || !checkOut.value) {
                return false;
            }

            return checkOut.value > checkIn.value;
        };

        const refreshAvailableRooms = async () => {
            if (!isDateRangeValid()) {
                recalculate();
                return;
            }

            const selectedRoomId = room.value;
            const requestId = ++latestRequestId;
            const params = new URLSearchParams({
                check_in_date: checkIn.value,
                check_out_date: checkOut.value,
            });

            if (excludeReservationId) {
                params.set('exclude_reservation_id', excludeReservationId);
            }

            try {
                const response = await fetch(`${availabilityEndpoint}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to load available rooms.');
                }

                const payload = await response.json();
                if (requestId !== latestRequestId) {
                    return;
                }

                const rooms = Array.isArray(payload.data) ? payload.data : [];
                room.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select room…';
                room.appendChild(placeholder);

                rooms.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.dataset.dailyRate = item.daily_rate ?? '0';
                    option.textContent = renderOptionLabel(item);

                    const keepSelected = selectedRoomId !== '' && selectedRoomId === item.id;
                    option.disabled = !item.is_available && !keepSelected;
                    option.selected = keepSelected;

                    room.appendChild(option);
                });

                if (selectedRoomId && !rooms.some((item) => item.id === selectedRoomId)) {
                    room.value = '';
                }
            } catch (error) {
                rateHint.textContent = error instanceof Error ? error.message : 'Unable to refresh room availability.';
            } finally {
                recalculate();
            }
        };

        [room, checkIn, checkOut].forEach((el) => el.addEventListener('change', recalculate));
        [checkIn, checkOut].forEach((el) => el.addEventListener('change', refreshAvailableRooms));
        room.addEventListener('focus', refreshAvailableRooms);

        refreshAvailableRooms();
        recalculate();
    })();
</script>
