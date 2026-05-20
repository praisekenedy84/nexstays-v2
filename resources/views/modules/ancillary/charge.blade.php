<x-layouts.app active-nav="ancillary" title="Post ancillary charge">
    <div class="mb-6"><a href="{{ route('tenant.ancillary-services.index') }}" class="text-sm text-primary hover:underline">← Services</a></div>
    <form method="POST" action="{{ route('tenant.ancillary-services.charge.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Reservation</label>
            <select name="reservation_id" required class="input-field">
                <option value="">Select…</option>
                @foreach ($reservations as $r)
                    <option value="{{ $r->id }}" @selected(old('reservation_id') === $r->id)>{{ $r->booking_ref }} — {{ $r->guest?->last_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Service (optional)</label>
            <select name="ancillary_service_id" class="input-field" id="svc">
                <option value="">Custom</option>
                @foreach ($services as $s)
                    <option value="{{ $s->id }}" data-price="{{ $s->default_price }}" @selected(old('ancillary_service_id') === $s->id)>{{ $s->name }} — @money($s->default_price)</option>
                @endforeach
            </select>
        </div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Amount override</label><input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Description</label><input name="description" value="{{ old('description') }}" class="input-field"></div>
        <button type="submit" class="btn-primary">Post to folio</button>
    </form>
</x-layouts.app>
