<?php

namespace Database\Factories;

use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'room_number' => (string) fake()->unique()->numberBetween(100, 999),
            'floor' => fake()->numberBetween(1, 5),
            'status' => 'vacant_clean',
            'daily_rate' => fake()->randomFloat(2, 50000, 400000),
        ];
    }
}
