<?php

declare(strict_types=1);

namespace Tests\Feature\HBMS;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\HBMS\Actions\CheckInGuest;
use App\Domain\HBMS\Actions\PostOverstayCharge;
use App\Domain\HBMS\Actions\SettleOverstay;
use App\Domain\Shared\Services\FolioService;
use DomainException;
use Tests\TenantTestCase;

class OverstayTest extends TenantTestCase
{
    private function overstayedReservation(): Reservation
    {
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'vacant_clean',
            'daily_rate' => '100000.00',
        ]);

        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in_date' => now()->subDays(3)->toDateString(),
            'check_out_date' => now()->subDay()->toDateString(),
            'daily_rate' => '100000.00',
        ]);

        app(CheckInGuest::class)->execute($reservation);

        return $reservation->fresh(['folio']);
    }

    public function test_overstay_charge_is_posted_to_folio_then_settled_by_payment(): void
    {
        $reservation = $this->overstayedReservation();

        $this->assertNotNull($reservation->overstayPreview());

        $chargeResponse = $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.reservations.overstay.charge', $reservation), [
                'notes' => 'Guest stayed extra night',
            ]);

        $chargeResponse->assertRedirect(route('tenant.reservations.show', $reservation));
        $chargeResponse->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame('pending', $reservation->overstay_settlement);
        $this->assertSame(1, $reservation->overstay_nights);
        $this->assertEquals(100000.0, (float) $reservation->overstay_charge);

        $this->assertDatabaseHas('folio_transactions', [
            'folio_id' => $reservation->folio->id,
            'transaction_type' => 'room_charge',
            'amount' => 100000,
        ]);

        $settleResponse = $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.reservations.overstay.settle', $reservation), [
                'settlement' => 'paid',
                'method' => 'cash',
            ]);

        $settleResponse->assertRedirect(route('tenant.reservations.show', $reservation));
        $settleResponse->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame('paid', $reservation->overstay_settlement);
        $this->assertNotNull($reservation->overstay_settlement_transaction_id);
        $this->assertNotNull($reservation->overstay_settlement_payment_id);
        $this->assertTrue(app(FolioService::class)->balance($reservation->folio)->isZero());
    }

    public function test_overstay_can_be_waived_with_reason(): void
    {
        $reservation = $this->overstayedReservation();

        $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.reservations.overstay.charge', $reservation))
            ->assertRedirect();

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.reservations.overstay.settle', $reservation), [
                'settlement' => 'waived',
                'waiver_reason' => 'Approved late checkout by duty manager',
            ]);

        $response->assertRedirect(route('tenant.reservations.show', $reservation));
        $response->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame('waived', $reservation->overstay_settlement);
        $this->assertSame('Approved late checkout by duty manager', $reservation->overstay_waiver_reason);
        $this->assertNotNull($reservation->overstay_settlement_transaction_id);
        $this->assertNotNull($reservation->overstay_settlement_payment_id);
        $this->assertTrue(app(FolioService::class)->balance($reservation->folio)->isZero());

        $this->assertDatabaseHas('folio_transactions', [
            'folio_id' => $reservation->folio->id,
            'transaction_type' => 'write_off',
        ]);
    }

    public function test_checkout_blocked_while_overstay_charge_is_pending(): void
    {
        $reservation = $this->overstayedReservation();

        $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.reservations.overstay.charge', $reservation));

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->from(route('tenant.reservations.show', $reservation))
            ->post(route('tenant.reservations.check-out', $reservation));

        $response->assertRedirect(route('tenant.reservations.show', $reservation));
        $response->assertSessionHas('error');
    }

    /**
     * Regression proxy for HBMS-2: posting the overstay charge a second time
     * (sequential, simulating a double-submit) must remain rejected once the
     * checks are moved inside a locked transaction.
     */
    public function test_posting_overstay_charge_twice_is_rejected(): void
    {
        $reservation = $this->overstayedReservation();

        app(PostOverstayCharge::class)->execute($reservation);

        $this->expectException(DomainException::class);

        app(PostOverstayCharge::class)->execute($reservation->fresh());
    }

    /**
     * HBMS-3: if overstay_settlement is "pending" but overstay_charge has been
     * corrupted to null, settling must fail with a clean DomainException
     * instead of an uncaught Brick\Money NumberFormatException.
     */
    public function test_settling_overstay_with_corrupted_null_charge_throws_domain_exception(): void
    {
        $reservation = $this->overstayedReservation();

        app(PostOverstayCharge::class)->execute($reservation);

        $reservation->refresh();
        $reservation->update(['overstay_charge' => null]);

        $this->expectException(DomainException::class);

        app(SettleOverstay::class)->execute($reservation->fresh(), 'paid', 'cash');
    }
}
