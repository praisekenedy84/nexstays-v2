<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Resources\Api\V1\OutletResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    use RespondsWithJsonApi;

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view-outlets'), 403);

        $outlets = Outlet::query()
            ->where('is_active', true)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderBy('name')
            ->get();

        return $this->respond(OutletResource::collection($outlets));
    }

    public function show(Request $request, Outlet $outlet): JsonResponse
    {
        abort_unless($request->user()?->can('view-outlets'), 403);

        return $this->respond(OutletResource::make($outlet));
    }

    public function tables(Request $request, Outlet $outlet): JsonResponse
    {
        abort_unless($request->user()?->can('view-outlets'), 403);

        $tables = $outlet->tables()->orderBy('table_number')->get();

        return $this->respond($tables->map(fn ($t) => [
            'id' => $t->id,
            'table_number' => $t->table_number,
            'capacity' => $t->capacity,
            'section' => $t->section,
            'status' => $t->status,
        ]));
    }

    public function menu(Request $request, Outlet $outlet): JsonResponse
    {
        abort_unless($request->user()?->can('view-menu'), 403);

        $categories = $outlet->menuCategories()
            ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return $this->respond($categories->map(fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'items' => $cat->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'is_available' => $item->is_available,
                'tags' => $item->tags,
            ]),
        ]));
    }
}
