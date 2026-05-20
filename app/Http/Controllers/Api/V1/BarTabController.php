<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Bar\Actions\OpenBarTab;
use App\Domain\Bar\Models\BarTab;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\Bar\OpenBarTabRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarTabController extends Controller
{
    use RespondsWithJsonApi;

    public function __construct(
        private readonly OpenBarTab $openBarTab
    ) {}

    public function index(Request $request, Outlet $outlet): JsonResponse
    {
        abort_unless($request->user()?->can('view-orders'), 403);
        abort_unless($outlet->isBar(), 422, 'Outlet is not a bar.');

        $tabs = BarTab::query()
            ->with(['order.items.menuItem'])
            ->where('outlet_id', $outlet->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->get();

        return $this->respond($tabs->map(fn (BarTab $tab) => [
            'id' => $tab->id,
            'guest_label' => $tab->guest_label,
            'order_id' => $tab->order_id,
            'total' => $tab->order?->total,
            'opened_at' => $tab->opened_at?->toIso8601String(),
        ]));
    }

    public function store(OpenBarTabRequest $request, Outlet $outlet): JsonResponse
    {
        abort_unless($outlet->isBar(), 422, 'Outlet is not a bar.');

        $tab = $this->openBarTab->execute(
            $outlet,
            $request->validated('guest_label'),
            $request->validated('folio_id')
        );

        return $this->respond([
            'id' => $tab->id,
            'guest_label' => $tab->guest_label,
            'order_id' => $tab->order_id,
        ], 201);
    }
}
