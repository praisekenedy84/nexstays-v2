<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Web\StoreMenuItemRequest;
use App\Http\Requests\Web\UpdateMenuItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    use RespondsWithJsonApi;

    public function store(StoreMenuItemRequest $request, Outlet $outlet): JsonResponse
    {
        $categoryId = $request->validated('category_id');
        abort_unless(
            $outlet->menuCategories()->whereKey($categoryId)->exists(),
            422,
            'Category does not belong to this outlet.'
        );

        $item = MenuItem::query()->create($request->validated());

        return $this->respond([
            'id' => $item->id,
            'name' => $item->name,
            'price' => $item->price,
            'is_available' => $item->is_available,
        ], 201);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        $menuItem->update($request->validated());

        return $this->respond([
            'id' => $menuItem->id,
            'name' => $menuItem->name,
            'price' => $menuItem->price,
            'is_available' => $menuItem->is_available,
        ]);
    }

    public function destroy(Request $request, MenuItem $menuItem): JsonResponse
    {
        abort_unless($request->user()?->can('manage-menu'), 403);

        $menuItem->delete();

        return $this->respond(['message' => 'Menu item deleted.']);
    }

    public function toggle(Request $request, MenuItem $menuItem): JsonResponse
    {
        abort_unless($request->user()?->can('manage-menu'), 403);

        $menuItem->update(['is_available' => ! $menuItem->is_available]);

        return $this->respond([
            'id' => $menuItem->id,
            'is_available' => $menuItem->is_available,
        ]);
    }
}
