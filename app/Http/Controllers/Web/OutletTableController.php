<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Models\OutletTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutletTableController extends Controller
{
    private const STATUSES = ['available', 'occupied', 'reserved', 'blocked'];

    public function index(Outlet $outlet): View
    {
        abort_unless(auth()->user()?->can('manage-outlets'), 403);

        $tables = $outlet->tables()
            ->withCount(['orders as active_orders_count' => fn ($q) => $q->whereNotIn('status', ['closed', 'voided'])])
            ->orderBy('section')
            ->orderBy('table_number')
            ->get();

        return view('modules.outlets.tables', compact('outlet', 'tables'));
    }

    public function store(Request $request, Outlet $outlet): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-outlets'), 403);

        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:20'],
            'capacity'     => ['required', 'integer', 'min:1', 'max:50'],
            'section'      => ['nullable', 'string', 'max:50'],
        ]);

        $exists = $outlet->tables()->where('table_number', $validated['table_number'])->exists();
        if ($exists) {
            return back()->with('error', "Table {$validated['table_number']} already exists in this outlet.")->withInput();
        }

        $outlet->tables()->create([
            'table_number' => $validated['table_number'],
            'capacity'     => $validated['capacity'],
            'section'      => $validated['section'] ?? null,
            'status'       => 'available',
        ]);

        return back()->with('success', "Table {$validated['table_number']} added.");
    }

    public function update(Request $request, Outlet $outlet, OutletTable $table): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-outlets'), 403);
        abort_unless($table->outlet_id === $outlet->id, 404);

        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:20'],
            'capacity'     => ['required', 'integer', 'min:1', 'max:50'],
            'section'      => ['nullable', 'string', 'max:50'],
        ]);

        $conflict = $outlet->tables()
            ->where('table_number', $validated['table_number'])
            ->where('id', '!=', $table->id)
            ->exists();

        if ($conflict) {
            return back()->with('error', "Table number {$validated['table_number']} is already used in this outlet.")->withInput();
        }

        $table->update([
            'table_number' => $validated['table_number'],
            'capacity'     => $validated['capacity'],
            'section'      => $validated['section'] ?? null,
        ]);

        return back()->with('success', "Table {$table->table_number} updated.");
    }

    public function updateStatus(Request $request, Outlet $outlet, OutletTable $table): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-outlets'), 403);
        abort_unless($table->outlet_id === $outlet->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', self::STATUSES)],
        ]);

        $table->update(['status' => $validated['status']]);

        return back()->with('success', "Table {$table->table_number} set to {$validated['status']}.");
    }

    public function destroy(Outlet $outlet, OutletTable $table): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-outlets'), 403);
        abort_unless($table->outlet_id === $outlet->id, 404);

        $activeOrders = $table->orders()->whereNotIn('status', ['closed', 'voided'])->count();

        if ($activeOrders > 0) {
            return back()->with('error', "Cannot delete table {$table->table_number} — it has {$activeOrders} active order(s).");
        }

        $number = $table->table_number;
        $table->delete();

        return back()->with('success', "Table {$number} deleted.");
    }
}
