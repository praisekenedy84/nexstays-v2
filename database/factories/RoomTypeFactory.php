<?php

namespace Database\Factories;

use App\Domain\HBMS\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???'));

        return [
            'name' => fake()->words(2, true),
            'code' => $code,
            'description' => fake()->sentence(),
            'max_adults' => 2,
            'max_children' => 1,
            'base_rate' => '150000.00',
        ];
    }
}
