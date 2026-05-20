<?php

namespace Database\Factories;

use App\Domain\HBMS\Models\Guest;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\HBMS\Support\BookingReferenceGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $checkIn = now()->addDays(1)->toDateString();
        $checkOut = now()->addDays(3)->toDateString();

        return [
            'booking_ref' => BookingReferenceGenerator::generate(),
            'guest_id' => Guest::factory(),
            'room_type_id' => RoomType::factory(),
            'status' => 'confirmed',
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'adults' => 2,
            'children' => 0,
            'daily_rate' => '150000.00',
            'deposit_amount' => 0,
        ];
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => ['status' => 'checked_in']);
    }
}
