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
                    <label for="source" class="mb-1.5 block text-xs font-medium text-ink-muted">Payment source</label>
                    <select id="source" name="source" required class="input-field">
                        @forelse ($enabledPaymentMethods as $method)
                            <option value="{{ $method }}" @selected(old('source', $reservation->source) === $method)>
                                {{ $paymentMethods[$method]['label'] }}
                            </option>
                        @empty
                            <option value="" disabled selected>No payment methods enabled</option>
                        @endforelse
                    </select>
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

        <div
            id="room_selection"
            class="space-y-4"
            data-available-room-types-url="{{ route('tenant.reservations.available-room-types') }}"
            data-available-rooms-url="{{ route('tenant.reservations.available-rooms') }}"
            data-exclude-reservation-id="{{ $reservation->id }}"
            data-initial-room-type-id="{{ old('room_type_id', $reservation->room_type_id ?? $reservation->room?->room_type_id) }}"
            data-initial-room-id="{{ old('room_id', $reservation->room_id) }}"
        >
            <div>
                <label for="room_type_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Room type</label>
                <select id="room_type_id" class="input-field">
                    <option value="">Select stay dates first…</option>
                </select>
                <p id="room_type_availability_hint" class="mt-1 text-xs text-ink-subtle">
                    Choose check-in and check-out dates to see room types.
                </p>
            </div>

            <div>
                <label for="room_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Room</label>
                <select id="room_id" name="room_id" required class="input-field">
                    <option value="">Select a room type first…</option>
                </select>
                <p id="room_availability_hint" class="mt-1 text-xs text-ink-subtle">
                    Choose a room type to see available rooms.
                </p>
                <p id="room_rate_hint" class="mt-1 text-xs text-ink-subtle"></p>
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
        const roomSelection = document.getElementById('room_selection');
        const roomType = document.getElementById('room_type_id');
        const room = document.getElementById('room_id');
        const checkIn = document.getElementById('check_in_date');
        const checkOut = document.getElementById('check_out_date');
        const rateHint = document.getElementById('room_rate_hint');
        const roomTypeHint = document.getElementById('room_type_availability_hint');
        const roomHint = document.getElementById('room_availability_hint');
        const estimatedTotal = document.getElementById('estimated_total');
        const roomTypesEndpoint = roomSelection?.dataset.availableRoomTypesUrl;
        const roomsEndpoint = roomSelection?.dataset.availableRoomsUrl;
        const excludeReservationId = roomSelection?.dataset.excludeReservationId;
        const initialRoomTypeId = roomSelection?.dataset.initialRoomTypeId || '';
        const initialRoomId = roomSelection?.dataset.initialRoomId || '';
        let latestRoomTypesRequestId = 0;
        let latestRoomsRequestId = 0;
        let appliedInitialSelection = false;

        if (!roomSelection || !roomType || !room || !checkIn || !checkOut || !estimatedTotal || !roomTypesEndpoint || !roomsEndpoint) {
            return;
        }

        const formatMoney = (value) =>
            new Intl.NumberFormat('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);

        const isDateRangeValid = () =>
            Boolean(checkIn.value && checkOut.value && checkOut.value > checkIn.value);

        const buildDateParams = () => {
            const params = new URLSearchParams({
                check_in_date: checkIn.value,
                check_out_date: checkOut.value,
            });

            if (excludeReservationId) {
                params.set('exclude_reservation_id', excludeReservationId);
            }

            return params;
        };

        const setSelectEnabled = (select, enabled, clearValue = true) => {
            select.disabled = !enabled;

            if (!enabled && clearValue) {
                select.value = '';
            }
        };

        const resetSelect = (select, placeholder) => {
            select.innerHTML = '';
            const option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;
            select.appendChild(option);
        };

        const recalculate = () => {
            const selectedOption = room.options[room.selectedIndex];
            const rate = Number(selectedOption?.dataset.dailyRate ?? 0);
            const inDate = checkIn.value ? new Date(checkIn.value + 'T00:00:00') : null;
            const outDate = checkOut.value ? new Date(checkOut.value + 'T00:00:00') : null;
            const nights = inDate && outDate ? Math.max(0, Math.round((outDate - inDate) / 86400000)) : 0;
            const total = nights * rate;

            rateHint.textContent = selectedOption?.value
                ? `Daily rate from room: TZS ${formatMoney(rate)}.`
                : '';

            estimatedTotal.textContent = nights > 0 && selectedOption?.value
                ? `${nights} night(s) x TZS ${formatMoney(rate)} = TZS ${formatMoney(total)} estimated stay total.`
                : 'Select stay dates and a room to preview total stay amount.';
        };

        const updateRoomTypeHint = (roomTypes) => {
            const typesWithAvailability = roomTypes.filter((item) => item.available_count > 0).length;

            roomTypeHint.textContent = roomTypes.length === 0
                ? 'No room types are configured.'
                : (typesWithAvailability === 0
                    ? 'No room types have availability for the selected dates.'
                    : `${typesWithAvailability} room type(s) with availability for the selected dates.`);
        };

        const renderRoomTypeLabel = (item) => {
            const baseRate = Number(item.base_rate ?? 0);
            const availability = item.available_count > 0
                ? `${item.available_count} available`
                : 'none available';

            return `${item.name} (${availability} · from TZS ${formatMoney(baseRate)})`;
        };

        const renderRoomLabel = (item) => {
            const statusLabel = String(item.status || '').replaceAll('_', ' ');
            const rate = Number(item.daily_rate ?? 0);
            const reason = item.reason === 'reserved_for_selected_dates'
                ? 'reserved for selected dates'
                : (item.reason === 'room_status_unavailable' ? 'not bookable by room status' : null);

            return `${item.room_number} (${statusLabel} · TZS ${formatMoney(rate)})${reason ? ` — ${reason}` : ''}`;
        };

        const refreshAvailableRooms = async () => {
            if (!isDateRangeValid() || !roomType.value) {
                setSelectEnabled(room, false);
                resetSelect(room, 'Select a room type first…');
                roomHint.textContent = 'Choose a room type to see available rooms.';
                rateHint.textContent = '';
                recalculate();
                return;
            }

            setSelectEnabled(room, true);
            roomHint.textContent = 'Loading available rooms…';

            const selectedRoomId = room.value;
            const requestId = ++latestRoomsRequestId;
            const params = buildDateParams();
            params.set('room_type_id', roomType.value);

            try {
                const response = await fetch(`${roomsEndpoint}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) {
                    throw new Error('Failed to load available rooms.');
                }

                const payload = await response.json();
                if (requestId !== latestRoomsRequestId) {
                    return;
                }

                const rooms = Array.isArray(payload.data) ? payload.data : [];
                resetSelect(room, 'Select room…');

                rooms.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.dataset.dailyRate = item.daily_rate ?? '0';
                    option.textContent = renderRoomLabel(item);

                    const keepSelected = selectedRoomId !== '' && selectedRoomId === item.id;
                    option.disabled = !item.is_available && !keepSelected;
                    option.selected = keepSelected;

                    room.appendChild(option);
                });

                if (!appliedInitialSelection && initialRoomId && roomType.value === initialRoomTypeId) {
                    const initialOption = Array.from(room.options).find((option) => option.value === initialRoomId);
                    if (initialOption) {
                        room.value = initialRoomId;
                    }
                    appliedInitialSelection = true;
                } else if (selectedRoomId && !rooms.some((item) => item.id === selectedRoomId)) {
                    room.value = '';
                }

                const availableCount = rooms.filter((item) => item.is_available).length;
                roomHint.textContent = availableCount === 0
                    ? 'No rooms of this type are available for the selected dates.'
                    : `${availableCount} room(s) available in this type.`;
            } catch (error) {
                roomHint.textContent = error instanceof Error ? error.message : 'Unable to refresh room availability.';
                rateHint.textContent = '';
            } finally {
                recalculate();
            }
        };

        const refreshAvailableRoomTypes = async () => {
            if (!isDateRangeValid()) {
                setSelectEnabled(roomType, false);
                resetSelect(roomType, 'Select stay dates first…');
                setSelectEnabled(room, false);
                resetSelect(room, 'Select a room type first…');
                roomTypeHint.textContent = 'Choose check-in and check-out dates to see room types.';
                roomHint.textContent = 'Choose a room type to see available rooms.';
                rateHint.textContent = '';
                recalculate();
                return;
            }

            setSelectEnabled(roomType, true);
            roomTypeHint.textContent = 'Loading room types…';

            const selectedRoomTypeId = roomType.value;
            const requestId = ++latestRoomTypesRequestId;
            const params = buildDateParams();

            try {
                const response = await fetch(`${roomTypesEndpoint}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) {
                    throw new Error('Failed to load room types.');
                }

                const payload = await response.json();
                if (requestId !== latestRoomTypesRequestId) {
                    return;
                }

                const roomTypes = Array.isArray(payload.data) ? payload.data : [];
                resetSelect(roomType, 'Select room type…');

                roomTypes.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = renderRoomTypeLabel(item);
                    option.disabled = item.available_count === 0
                        && item.id !== selectedRoomTypeId
                        && item.id !== initialRoomTypeId;
                    roomType.appendChild(option);
                });

                if (!appliedInitialSelection && initialRoomTypeId) {
                    const initialTypeOption = Array.from(roomType.options).find((option) => option.value === initialRoomTypeId);
                    if (initialTypeOption) {
                        roomType.value = initialRoomTypeId;
                        updateRoomTypeHint(roomTypes);
                        await refreshAvailableRooms();
                        return;
                    }

                    appliedInitialSelection = true;
                }

                if (selectedRoomTypeId && roomTypes.some((item) => item.id === selectedRoomTypeId)) {
                    roomType.value = selectedRoomTypeId;
                    updateRoomTypeHint(roomTypes);
                    await refreshAvailableRooms();
                    return;
                }

                setSelectEnabled(room, false);
                resetSelect(room, 'Select a room type first…');
                roomHint.textContent = 'Choose a room type to see available rooms.';
                rateHint.textContent = '';
                updateRoomTypeHint(roomTypes);
            } catch (error) {
                roomTypeHint.textContent = error instanceof Error ? error.message : 'Unable to refresh room types.';
                setSelectEnabled(room, false);
                resetSelect(room, 'Select a room type first…');
                roomHint.textContent = 'Choose a room type to see available rooms.';
                rateHint.textContent = '';
            } finally {
                recalculate();
            }
        };

        const onDatesChanged = () => {
            appliedInitialSelection = true;
            roomType.value = '';
            refreshAvailableRoomTypes();
        };

        const debounce = (fn, delayMs) => {
            let timer = null;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn(...args), delayMs);
            };
        };

        const onDatesChangedDebounced = debounce(onDatesChanged, 300);

        [room, checkIn, checkOut].forEach((el) => el.addEventListener('change', recalculate));
        [checkIn, checkOut].forEach((el) => el.addEventListener('change', onDatesChangedDebounced));
        roomType.addEventListener('change', refreshAvailableRooms);

        room.closest('form')?.addEventListener('submit', () => {
            room.disabled = false;
        });

        refreshAvailableRoomTypes();
        recalculate();
    })();
</script>
