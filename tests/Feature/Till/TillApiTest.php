<?php

declare(strict_types=1);

namespace Tests\Feature\Till;

use App\Domain\Shared\Models\Outlet;
use App\Domain\Till\Models\TillSession;
use Tests\TenantTestCase;

class TillApiTest extends TenantTestCase
{
    public function test_it_opens_and_closes_a_till_session(): void
    {
        $this->user->givePermissionTo('manage-till');

        $outlet = Outlet::query()->create([
            'name' => 'Test Bar',
            'type' => 'bar',
            'is_active' => true,
        ]);

        $open = $this->tenantJson('POST', '/api/v1/tills/open', [
            'outlet_id' => $outlet->id,
            'float_amount' => '50000',
            'currency' => 'TZS',
        ]);

        $open->assertCreated();
        $sessionId = $open->json('data.id');

        $close = $this->tenantJson('POST', "/api/v1/tills/{$sessionId}/close", [
            'declared_cash' => '50000',
        ]);

        $close->assertOk();
        $this->assertDatabaseHas('till_sessions', [
            'id' => $sessionId,
            'status' => 'closed',
        ]);
    }

    public function test_cash_payment_succeeds_without_a_till_session(): void
    {
        $this->user->givePermissionTo(['manage-orders', 'manage-till']);

        $outlet = Outlet::query()->where('type', 'restaurant')->first()
            ?? Outlet::query()->create(['name' => 'R', 'type' => 'restaurant', 'is_active' => true]);

        $orderResponse = $this->tenantJson('POST', "/api/v1/outlets/{$outlet->id}/orders", [
            'covers' => 2,
        ]);

        $orderId = $orderResponse->json('data.id');

        $this->tenantJson('POST', "/api/v1/orders/{$orderId}/cash-payment", [
            'amount_tendered' => '0',
        ])->assertOk();

        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'method' => 'cash',
            'till_session_id' => null,
        ]);
    }

    public function test_cash_payment_requires_active_till(): void
    {
        $this->user->givePermissionTo(['manage-orders', 'manage-till']);

        $outlet = Outlet::query()->where('type', 'restaurant')->first()
            ?? Outlet::query()->create(['name' => 'R', 'type' => 'restaurant', 'is_active' => true]);

        $orderResponse = $this->tenantJson('POST', "/api/v1/outlets/{$outlet->id}/orders", [
            'covers' => 2,
        ]);

        $orderId = $orderResponse->json('data.id');

        $this->tenantJson('POST', "/api/v1/orders/{$orderId}/cash-payment", [
            'till_session_id' => (string) \Illuminate\Support\Str::uuid(),
            'amount_tendered' => '1000',
        ])->assertUnprocessable();
    }
}
