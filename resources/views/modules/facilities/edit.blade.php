<x-layouts.app :active-nav="$navId" :title="$facilityLabel" subtitle="Edit attendance record">
    <div class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('tenant.facilities.attendance.update', $attendance) }}" class="card space-y-4 p-6">
            @csrf
            @method('PATCH')

            <h3 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Edit visit</h3>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Visitor</label>
                @if ($attendance->reservation_id)
                    <input type="text" value="{{ $attendance->visitor_name }}" class="input-field" disabled>
                    <p class="mt-1 text-xs text-ink-muted">Hotel guest name is taken from the reservation and cannot be changed here.</p>
                @else
                    <input type="text" name="visitor_name" value="{{ old('visitor_name', $attendance->visitor_name) }}" class="input-field">
                @endif
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Number of people</label>
                <input type="number" step="1" min="1" name="party_size" value="{{ old('party_size', $attendance->party_size) }}" class="input-field">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Fee amount</label>
                <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $attendance->amount) }}" class="input-field" @if ($attendance->settlement === 'complimentary') readonly @endif>
                @if ($attendance->settlement === 'complimentary')
                    <p class="mt-1 text-xs text-ink-muted">Complimentary visits must remain free of charge.</p>
                @else
                    <p class="mt-1 text-xs text-ink-muted">Settlement method ({{ str_replace('_', ' ', $attendance->settlement) }}) cannot be changed here. To change it, delete this record and record a new visit.</p>
                @endif
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Notes (optional)</label>
                <input type="text" name="notes" value="{{ old('notes', $attendance->notes) }}" class="input-field">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary">Save changes</button>
                <a href="{{ route($backRoute) }}" class="btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.app>
