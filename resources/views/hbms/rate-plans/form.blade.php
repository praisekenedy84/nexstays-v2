@php $isEdit = $ratePlan->exists; @endphp
<x-layouts.app active-nav="rate-plans" :title="$isEdit ? 'Edit rate plan' : 'New rate plan'">
    <div class="mb-6"><a href="{{ route('tenant.rate-plans.index') }}" class="text-sm text-primary hover:underline">← Rate plans</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.rate-plans.update', $ratePlan) : route('tenant.rate-plans.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label><input name="name" value="{{ old('name', $ratePlan->name) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Code</label><input name="code" value="{{ old('code', $ratePlan->code) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Type</label><input name="type" value="{{ old('type', $ratePlan->type) }}" class="input-field" placeholder="e.g. BAR, RACK"></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Valid from</label><input type="date" name="valid_from" value="{{ old('valid_from', $ratePlan->valid_from?->format('Y-m-d')) }}" class="input-field"></div>
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Valid to</label><input type="date" name="valid_to" value="{{ old('valid_to', $ratePlan->valid_to?->format('Y-m-d')) }}" class="input-field"></div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $ratePlan->is_active ?? true))> Active</label>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
