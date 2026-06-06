<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OrderAuthorizationService
{
    /**
     * May view all staff sales, totals, and filters on the completed-sales report.
     */
    public function canViewAllSales(User $user): bool
    {
        return $user->can('view-all-orders')
            || $user->can('manage-all-orders')
            || $user->can('manage-orders');
    }

    public function canView(User $user, Order $order): bool
    {
        if (! $user->can('view-orders')) {
            return false;
        }

        if ($this->canViewAllSales($user)) {
            return true;
        }

        if ($this->canProcessKitchen($user)) {
            return true;
        }

        return $this->ownsOrder($user, $order);
    }

    public function canManageAny(User $user): bool
    {
        return $user->can('manage-all-orders') || $user->can('manage-orders');
    }

    public function canManage(User $user, Order $order): bool
    {
        if ($this->canManageAny($user)) {
            return true;
        }

        if (! $user->can('manage-own-orders')) {
            return false;
        }

        return $this->ownsOrder($user, $order);
    }

    /**
     * Permanent deletion is manager-only and limited to voided orders.
     * Staff must void settled orders instead of deleting them.
     */
    public function canDelete(User $user, Order $order): bool
    {
        if (! $this->canManageAny($user)) {
            return false;
        }

        return $order->status === 'voided';
    }

    public function canCreate(User $user): bool
    {
        return $this->canManageAny($user) || $user->can('manage-own-orders');
    }

    public function canProcessKitchen(User $user): bool
    {
        return $user->can('process-kitchen-orders');
    }

    public function canUpdateItemStatus(User $user, Order $order): bool
    {
        return $this->canManage($user, $order)
            || ($this->canProcessKitchen($user) && $this->canView($user, $order));
    }

    /**
     * Restrict completed-sales listings to own orders unless the user may view all.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeSalesListing(Builder $query, User $user): Builder
    {
        if ($this->canViewAllSales($user)) {
            return $query;
        }

        return $query->where('waiter_id', $user->id);
    }

    private function ownsOrder(User $user, Order $order): bool
    {
        return $order->waiter_id !== null
            && (string) $order->waiter_id === (string) $user->id;
    }
}
