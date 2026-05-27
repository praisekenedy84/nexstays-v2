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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $request->input('outlet_id');
        $sort     = in_array($request->query('sort'), ['name', 'price', 'created_at']) ? $request->query('sort') : 'name';
        $dir      = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search   = trim((string) $request->query('search', ''));

        $items = MenuItem::query()
            ->with('category.outlet')
            ->when($outletId, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('outlet_id', $outletId)))
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->paginate(30)
            ->withQueryString();

        return view('modules.menu.index', [
            'items'    => $items,
            'outlets'  => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'outletId' => $outletId,
            'sort'     => $sort,
            'dir'      => $dir,
            'search'   => $search,
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
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('menu-items/photos', MenuItem::photosDisk());
        }

        MenuItem::query()->create($validated);

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
        $validated = $request->validated();
        $disk = MenuItem::photosDisk();

        if ($request->boolean('remove_photo') && $menuItem->photo) {
            Storage::disk($disk)->delete($menuItem->photo);
            $validated['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($menuItem->photo) {
                Storage::disk($disk)->delete($menuItem->photo);
            }
            $validated['photo'] = $request->file('photo')->store('menu-items/photos', $disk);
        }

        $menuItem->update($validated);

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $menuItem->category->outlet_id])
            ->with('success', 'Menu item updated.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $outletId = $menuItem->category->outlet_id;

        if ($menuItem->photo) {
            Storage::disk(MenuItem::photosDisk())->delete($menuItem->photo);
        }

        $menuItem->delete();

        return redirect()
            ->route('tenant.menu-items.index', ['outlet_id' => $outletId])
            ->with('success', 'Menu item deleted.');
    }
}
