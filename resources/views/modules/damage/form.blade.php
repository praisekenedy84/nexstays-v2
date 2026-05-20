@php $isEdit = $damage->exists; @endphp
<x-layouts.app active-nav="damage" :title="$isEdit ? 'Edit damage report' : 'Report damage'">
    <div class="mb-6"><a href="{{ route('tenant.damages.index') }}" class="text-sm text-primary hover:underline">← Damage</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.damages.update', $damage) : route('tenant.damages.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Room</label>
            <select name="room_id" required class="input-field">
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected(old('room_id', $damage->room_id ?? null) === $room->id)>Room {{ $room->room_number }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Reservation (optional)</label>
            <select name="reservation_id" class="input-field"><option value="">—</option>
                @foreach ($reservations as $r)
                    <option value="{{ $r->id }}" @selected(old('reservation_id', $damage->reservation_id ?? null) === $r->id)>{{ $r->booking_ref }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Description</label><textarea name="description" required rows="3" class="input-field">{{ old('description', $damage->description ?? '') }}</textarea></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Estimated cost</label><input type="number" step="0.01" name="estimated_cost" value="{{ old('estimated_cost', $damage->estimated_cost ?? null) }}" class="input-field"></div>
        @if ($isEdit)
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Status</label>
                <select name="status" class="input-field">
                    @foreach (['reported', 'invoiced', 'resolved'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $damage->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Resolution notes</label><textarea name="resolution_notes" class="input-field" rows="2">{{ old('resolution_notes', $damage->resolution_notes) }}</textarea></div>
        @endif
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
