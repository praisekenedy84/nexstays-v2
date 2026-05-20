<?php

declare(strict_types=1);

namespace App\Domain\Bar\Actions;

use App\Domain\Bar\Models\BarTab;
use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Models\Outlet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OpenBarTab
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function execute(Outlet $outlet, string $guestLabel, ?string $folioId = null): BarTab
    {
        return DB::transaction(function () use ($outlet, $guestLabel, $folioId) {
            $order = $this->orderService->create($outlet, [
                'guest_label' => $guestLabel,
                'folio_id' => $folioId,
            ]);

            return BarTab::query()->create([
                'outlet_id' => $outlet->id,
                'order_id' => $order->id,
                'folio_id' => $folioId,
                'guest_label' => $guestLabel,
                'status' => 'open',
                'opened_by' => Auth::id(),
            ]);
        });
    }
}
