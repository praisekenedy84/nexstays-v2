<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\HBMS\Models\Guest;
use App\Domain\HBMS\Models\RatePlan;
use App\Domain\HBMS\Models\RatePlanPrice;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\HBMS\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoHotelSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $password = config('nexstay.demo.password');

        $admin = User::query()->updateOrCreate(
            ['email' => config('nexstay.demo.admin_email')],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['general_manager', 'super_admin']);

        $frontDesk = User::query()->updateOrCreate(
            ['email' => config('nexstay.demo.front_desk_email')],
            [
                'name' => 'Demo Front Desk',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
        $frontDesk->syncRoles(['front_desk']);

        $housekeeper = User::query()->updateOrCreate(
            ['email' => config('nexstay.demo.housekeeper_email')],
            [
                'name' => 'Demo Housekeeper',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
        $housekeeper->syncRoles(['housekeeper']);

        $standard = RoomType::query()->updateOrCreate(
            ['code' => 'STD'],
            [
                'name' => 'Standard Room',
                'description' => 'Comfortable standard room with garden view.',
                'max_adults' => 2,
                'max_children' => 1,
                'base_rate' => '150000.00',
                'amenities' => ['wifi', 'ac', 'tv'],
            ]
        );

        $deluxe = RoomType::query()->updateOrCreate(
            ['code' => 'DLX'],
            [
                'name' => 'Deluxe Room',
                'description' => 'Spacious deluxe room with ocean view.',
                'max_adults' => 3,
                'max_children' => 2,
                'base_rate' => '250000.00',
                'amenities' => ['wifi', 'ac', 'tv', 'minibar'],
            ]
        );

        $rack = RatePlan::query()->updateOrCreate(
            ['code' => 'RACK'],
            [
                'name' => 'Rack Rate',
                'type' => 'public',
                'currency' => config('nexstay.currency.default', 'TZS'),
                'is_active' => true,
            ]
        );

        foreach ([$standard, $deluxe] as $roomType) {
            RatePlanPrice::query()->updateOrCreate(
                [
                    'rate_plan_id' => $rack->id,
                    'room_type_id' => $roomType->id,
                    'date' => now()->toDateString(),
                ],
                ['price' => $roomType->base_rate]
            );
        }

        $rooms = [
            ['room_type_id' => $standard->id, 'room_number' => '101', 'floor' => 1, 'status' => 'vacant_clean'],
            ['room_type_id' => $standard->id, 'room_number' => '102', 'floor' => 1, 'status' => 'vacant_clean'],
            ['room_type_id' => $standard->id, 'room_number' => '103', 'floor' => 1, 'status' => 'occupied'],
            ['room_type_id' => $deluxe->id, 'room_number' => '201', 'floor' => 2, 'status' => 'vacant_clean'],
            ['room_type_id' => $deluxe->id, 'room_number' => '202', 'floor' => 2, 'status' => 'vacant_dirty'],
        ];

        foreach ($rooms as $roomData) {
            Room::query()->updateOrCreate(
                ['room_number' => $roomData['room_number']],
                $roomData
            );
        }

        $guestA = Guest::query()->updateOrCreate(
            ['email' => 'amina.hassan@example.com'],
            [
                'first_name' => 'Amina',
                'last_name' => 'Hassan',
                'phone' => '+255712345678',
                'nationality' => 'TZ',
                'vip_level' => 1,
            ]
        );

        $guestB = Guest::query()->updateOrCreate(
            ['email' => 'james.okello@example.com'],
            [
                'first_name' => 'James',
                'last_name' => 'Okello',
                'phone' => '+255798765432',
                'nationality' => 'KE',
            ]
        );

        $room103 = Room::query()->where('room_number', '103')->first();

        Reservation::query()->updateOrCreate(
            ['booking_ref' => 'NX-DEMO-ARRIVAL'],
            [
                'guest_id' => $guestA->id,
                'room_type_id' => $standard->id,
                'room_id' => $room103?->id,
                'rate_plan_id' => $rack->id,
                'status' => 'confirmed',
                'check_in_date' => now()->toDateString(),
                'check_out_date' => now()->addDays(3)->toDateString(),
                'adults' => 2,
                'children' => 0,
                'daily_rate' => $standard->base_rate,
                'source' => 'walk_in',
            ]
        );

        Reservation::query()->updateOrCreate(
            ['booking_ref' => 'NX-DEMO-INQUIRY'],
            [
                'guest_id' => $guestB->id,
                'room_type_id' => $deluxe->id,
                'status' => 'inquiry',
                'check_in_date' => now()->addDays(5)->toDateString(),
                'check_out_date' => now()->addDays(8)->toDateString(),
                'adults' => 2,
                'children' => 1,
                'daily_rate' => $deluxe->base_rate,
                'source' => 'phone',
            ]
        );
    }
}
