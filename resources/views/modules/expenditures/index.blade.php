<x-layouts.app active-nav="expenditures" title="Expenditures" subtitle="Operational expense tracking">
    @can('manage-expenditures')
        <div class="mb-4 flex justify-end">
            <a href="{{ route('tenant.expenditures.create') }}" class="btn-primary">Record expense</a>
        </div>
    @endcan

    <div class="mb-4 rounded-xl bg-slate-50 px-4 py-3 text-sm">
        Period total: <strong class="text-ink">@money($total)</strong>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <select name="category" class="input-field w-auto" onchange="this.form.submit()">
            <option value="">All categories</option>
            @foreach (\App\Domain\Expenditures\Models\Expenditure::CATEGORIES as $cat)
                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ ucfirst($cat) }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="input-field w-auto" onchange="this.form.submit()">
        <input type="date" name="to" value="{{ request('to') }}" class="input-field w-auto" onchange="this.form.submit()">
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Description</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    @can('manage-expenditures')<th class="px-5 py-3"></th>@endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($expenditures as $row)
                    <tr>
                        <td class="px-5 py-4 text-ink-muted">{{ $row->expense_date->format('d M Y') }}</td>
                        <td class="px-5 py-4 capitalize">{{ $row->category }}</td>
                        <td class="px-5 py-4">{{ $row->description }}</td>
                        <td class="px-5 py-4 text-right font-medium">@money($row->amount)</td>
                        @can('manage-expenditures')
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('tenant.expenditures.edit', $row) }}" class="text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.expenditures.destroy', $row) }}" class="ml-3 inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $expenditures->links() }}</div>
    </div>
</x-layouts.app>
