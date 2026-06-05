<?php

declare(strict_types=1);

namespace Tests\Feature\HBMS;

use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\DivisionSalesService;
use App\Domain\Shared\Services\ReportingService;
use App\Models\User;
use Tests\TenantTestCase;

class ReservationForceDeleteTest extends TenantTestCase
{
    public function test_admin_can_force_delete_checked_in_reservation_with_folio(): void
    {
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'occupied',
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
            'transaction_type' => 'room_charge',
            'description' => 'Night 1',
            'amount' => 150000,
            'tax_amount' => 0,
            'posted_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.reservations.destroy', $reservation));

        $response->assertRedirect(route('tenant.reservations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
        $this->assertDatabaseMissing('folios', ['id' => $folio->id]);
        $this->assertDatabaseMissing('folio_transactions', ['folio_id' => $folio->id]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'vacant_clean',
        ]);
    }

    public function test_front_desk_cannot_force_delete_checked_out_reservation(): void
    {
        $frontDesk = User::factory()->create();
        $frontDesk->assignRole('front_desk');

        $reservation = Reservation::factory()->create([
            'status' => 'checked_out',
            'check_in_date' => now()->subDays(3)->toDateString(),
            'check_out_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->web()
            ->actingAs($frontDesk, 'web')
            ->delete(route('tenant.reservations.destroy', $reservation));

        $response->assertForbidden();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
    }

    public function test_front_desk_cannot_delete_reservation_with_folio_transactions(): void
    {
        $frontDesk = User::factory()->create();
        $frontDesk->assignRole('front_desk');

        $reservation = Reservation::factory()->create(['status' => 'confirmed']);
        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'misc',
            'description' => 'Manual charge',
            'amount' => 25000,
            'tax_amount' => 0,
            'posted_at' => now(),
        ]);

        $response = $this->web()
            ->actingAs($frontDesk, 'web')
            ->delete(route('tenant.reservations.destroy', $reservation));

        $response->assertForbidden();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
    }

    public function test_force_deleted_reservation_is_excluded_from_room_reports(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'checked_in',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'daily_rate' => '100000.00',
        ]);

        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'room_charge',
            'description' => 'Night 1',
            'amount' => 100000,
            'tax_amount' => 0,
            'posted_at' => now(),
        ]);

        $from = now()->startOfDay();
        $to = now()->addDays(3)->endOfDay();

        $beforeDelete = app(ReportingService::class)
            ->roomReservationFinance($from, $to);
        $this->assertEquals(1, $beforeDelete['total_reservations']);

        $beforeSales = app(DivisionSalesService::class)->liveSummary();
        $this->assertEquals(100000.0, $beforeSales['rooms']);

        $this->web()
            ->actingAs($this->user, 'web')
            ->delete(route('tenant.reservations.destroy', $reservation))
            ->assertRedirect(route('tenant.reservations.index'));

        $afterDelete = app(ReportingService::class)
            ->roomReservationFinance($from, $to);
        $this->assertEquals(0, $afterDelete['total_reservations']);

        $accounting = app(ReportingService::class)
            ->roomPaymentsAccounting($from, $to);
        $this->assertCount(0, $accounting['rows']);

        $afterSales = app(DivisionSalesService::class)->liveSummary();
        $this->assertEquals(0.0, $afterSales['rooms']);
    }
}
