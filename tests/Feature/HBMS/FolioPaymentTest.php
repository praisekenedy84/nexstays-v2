<?php

declare(strict_types=1);

namespace Tests\Feature\HBMS;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\FolioService;
use Tests\TenantTestCase;

class FolioPaymentTest extends TenantTestCase
{
    public function test_staff_can_record_folio_payment_from_web(): void
    {
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'vacant_clean',
        ]);
        $reservation = Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'status' => 'checked_in',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
        ]);

        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'restaurant',
            'description' => 'Dinner',
            'amount' => 50000,
            'tax_amount' => 9000,
            'posted_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->post(route('tenant.folios.payments.store', $folio), [
                'amount' => 50000,
                'method' => 'cash',
                'notes' => 'Front desk collection',
            ]);

        $response->assertRedirect(route('tenant.reservations.show', $reservation));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('folio_transactions', [
            'folio_id' => $folio->id,
            'transaction_type' => 'payment',
            'amount' => -50000,
        ]);

        $this->assertDatabaseHas('payments', [
            'folio_id' => $folio->id,
            'amount' => 50000,
            'method' => 'cash',
            'notes' => 'Front desk collection',
        ]);

        $this->assertTrue(app(FolioService::class)->balance($folio->fresh())->isZero());
    }

    public function test_folio_payment_rejects_amount_over_balance(): void
    {
        $reservation = Reservation::factory()->create(['status' => 'checked_in']);
        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'misc',
            'description' => 'Mini bar',
            'amount' => 10000,
            'tax_amount' => 0,
            'posted_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->from(route('tenant.reservations.show', $reservation))
            ->post(route('tenant.folios.payments.store', $folio), [
                'amount' => 20000,
                'method' => 'cash',
            ]);

        $response->assertRedirect(route('tenant.reservations.show', $reservation));
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('payments', [
            'folio_id' => $folio->id,
        ]);
    }

    public function test_reservation_page_shows_collect_payment_form_when_balance_due(): void
    {
        $reservation = Reservation::factory()->create(['status' => 'checked_in']);
        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'restaurant',
            'description' => 'Lunch',
            'amount' => 25000,
            'tax_amount' => 4500,
            'posted_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reservations.show', $reservation));

        $response->assertOk();
        $response->assertSee('Collect payment');
        $response->assertSee('Record payment');
        $response->assertSee('Balance due');
    }
}
