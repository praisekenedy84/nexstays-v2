<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreMenuItemRequest;
use App\Http\Requests\Web\UpdateMenuItemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $request->input('outlet_id');

        $items = MenuItem::query()
            ->with('category.outlet')
            ->when($outletId, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('outlet_id', $outletId)))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('modules.menu.index', [
            'items' => $items,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'outletId' => $outletId,
        ]);
    }

    public function create(Request $request): View
    {
        $outlets = Outlet::query()->where('is_active', true)->orderBy('name')->get();
        $categories = MenuCategory::query()
            ->with('outlet')
            ->when($request->filled('outlet_id'), fn ($q) => $q->where('outlet_id', $request->input('outlet_id')))
            ->orderBy('name')
            ->get();

        return view('modules.menu.form', [
            'item' => new MenuItem(['is_available' => true, 'price' => '0']),
            'outlets' => $outlets,
            'categories' => $categories,
        ]);
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        MenuItem::query()->create($request->validated());

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $request->input('outlet_filter')])
            ->with('success', 'Menu item created.');
    }

    public function edit(MenuItem $menuItem): View
    {
        $menuItem->load('category.outlet');

        return view('modules.menu.form', [
            'item' => $menuItem,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => MenuCategory::query()->with('outlet')->where('outlet_id', $menuItem->category->outlet_id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $menuItem->update($request->validated());

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $menuItem->category->outlet_id])
            ->with('success', 'Menu item updated.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $outletId = $menuItem->category->outlet_id;
        $menuItem->delete();

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $outletId])
            ->with('success', 'Menu item deleted.');
    }
}
