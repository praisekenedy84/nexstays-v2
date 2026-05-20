<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreMenuCategoryRequest;
use App\Http\Requests\Web\UpdateMenuCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MenuCategoryController extends Controller
{
    public function index(): View
    {
        $categories = MenuCategory::query()->with('outlet')->orderBy('name')->paginate(30);

        return view('modules.menu.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('modules.menu.categories.form', [
            'category' => new MenuCategory(['is_active' => true]),
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreMenuCategoryRequest $request): RedirectResponse
    {
        MenuCategory::query()->create($request->validated());

        return redirect()->route('tenant.menu-categories.index')->with('success', 'Category created.');
    }

    public function edit(MenuCategory $menuCategory): View
    {
        return view('modules.menu.categories.form', [
            'category' => $menuCategory,
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateMenuCategoryRequest $request, MenuCategory $menuCategory): RedirectResponse
    {
        $menuCategory->update($request->validated());

        return redirect()->route('tenant.menu-categories.index')->with('success', 'Category updated.');
    }

    public function destroy(MenuCategory $menuCategory): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-menu'), 403);
        abort_if($menuCategory->items()->exists(), 403, 'Remove menu items in this category first.');

        $menuCategory->delete();

        return redirect()->route('tenant.menu-categories.index')->with('success', 'Category removed.');
    }
}
