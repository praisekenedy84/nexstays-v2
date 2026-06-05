<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Services;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Services\BeverageStockLinkService;
use App\Domain\Shared\Models\MenuItem;
use App\Domain\Shared\Models\Order;
use App\Domain\Till\Models\Payment;
use App\Domain\Till\Models\TillMovement;

class OrderSettlementReversalService
{
    public function __construct(
        private readonly BeverageStockLinkService $beverageStockLink
    ) {}

    public function execute(Order $order, string $voidReason): void
    {
        $order->loadMissing(['outlet', 'items.menuItem']);

        FolioTransaction::query()
            ->where('reference_id', $order->id)
            ->where('reference_type', Order::class)
            ->whereNull('voided_at')
            ->update([
                'voided_at' => now(),
                'void_reason' => $voidReason,
            ]);

        $payments = Payment::query()->where('order_id', $order->id)->get();

        foreach ($payments as $payment) {
            TillMovement::query()
                ->where('reference_id', $payment->id)
                ->where('reference_type', Payment::class)
                ->delete();

            $payment->delete();
        }

        $this->reverseInventoryDeductions($order);

        StockMovement::query()
            ->where('reference_id', $order->id)
            ->where('reference_type', Order::class)
            ->delete();
    }

    private function reverseInventoryDeductions(Order $order): void
    {
        if (! $order->outlet?->tracksBeverageInventory()) {
            return;
        }

        $consumptionMovements = StockMovement::query()
            ->with('stockItem')
            ->where('reference_id', $order->id)
            ->where('reference_type', Order::class)
            ->where('movement_type', 'consumption')
            ->get();

        $menuItemIds = $order->items
            ->where('status', '!=', 'voided')
            ->pluck('menu_item_id')
            ->unique()
            ->filter();

        foreach ($consumptionMovements as $movement) {
            $stockItem = $movement->stockItem;
            if ($stockItem === null) {
                continue;
            }

            $restoreQty = abs((float) $movement->quantity);
            if ($restoreQty <= 0) {
                continue;
            }

            $stockItem->increment('current_stock', $restoreQty);
        }

        MenuItem::query()
            ->with(['recipeIngredients.stockItem', 'category.outlet'])
            ->whereIn('id', $menuItemIds)
            ->each(fn (MenuItem $menuItem) => $this->beverageStockLink->syncMenuAvailability($menuItem));
    }
}
