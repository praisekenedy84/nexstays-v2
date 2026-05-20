@php $isEdit = $expenditure->exists; @endphp
<x-layouts.app active-nav="expenditures" :title="$isEdit ? 'Edit expenditure' : 'Record expenditure'">
    <div class="mb-6"><a href="{{ route('tenant.expenditures.index') }}" class="text-sm text-primary hover:underline">← Expenditures</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.expenditures.update', $expenditure) : route('tenant.expenditures.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Category</label>
            <select name="category" class="input-field" required>
                @foreach (\App\Domain\Expenditures\Models\Expenditure::CATEGORIES as $cat)
                    <option value="{{ $cat }}" @selected(old('category', $expenditure->category) === $cat)>{{ ucfirst($cat) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Outlet (optional)</label>
            <select name="outlet_id" class="input-field">
                <option value="">Property-wide</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet->id }}" @selected(old('outlet_id', $expenditure->outlet_id) === $outlet->id)>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Amount (TZS)</label>
            <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expenditure->amount) }}" required class="input-field">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Description</label>
            <input name="description" value="{{ old('description', $expenditure->description) }}" required class="input-field">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Reference</label>
            <input name="reference" value="{{ old('reference', $expenditure->reference) }}" class="input-field">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Expense date</label>
            <input type="date" name="expense_date" value="{{ old('expense_date', $expenditure->expense_date?->format('Y-m-d')) }}" required class="input-field">
        </div>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
