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

        $admin = User::withTrashed()->updateOrCreate(
            ['email' => config('nexstay.demo.admin_email')],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
        if ($admin->trashed()) {
            $admin->restore();
        }
        $admin->syncRoles(['general_manager', 'super_admin']);

        $frontDesk = User::withTrashed()->updateOrCreate(
            ['email' => config('nexstay.demo.front_desk_email')],
            [
                'name' => 'Demo Front Desk',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
        if ($frontDesk->trashed()) {
            $frontDesk->restore();
        }
        $frontDesk->syncRoles(['front_desk']);

        $housekeeper = User::withTrashed()->updateOrCreate(
            ['email' => config('nexstay.demo.housekeeper_email')],
            [
                'name' => 'Demo Housekeeper',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
        if ($housekeeper->trashed()) {
            $housekeeper->restore();
        }
        $housekeeper->syncRoles(['housekeeper']);

        $standard = RoomType::withTrashed()->updateOrCreate(
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
        if ($standard->trashed()) {
            $standard->restore();
        }

        $deluxe = RoomType::withTrashed()->updateOrCreate(
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
        if ($deluxe->trashed()) {
            $deluxe->restore();
        }

        $rack = RatePlan::withTrashed()->updateOrCreate(
            ['code' => 'RACK'],
            [
                'name' => 'Rack Rate',
                'type' => 'public',
                'currency' => config('nexstay.currency.default', 'TZS'),
                'is_active' => true,
            ]
        );
        if ($rack->trashed()) {
            $rack->restore();
        }

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
            $room = Room::withTrashed()->updateOrCreate(
                ['room_number' => $roomData['room_number']],
                $roomData
            );
            if ($room->trashed()) {
                $room->restore();
            }
        }

        $guestA = Guest::withTrashed()->updateOrCreate(
            ['email' => 'amina.hassan@example.com'],
            [
                'first_name' => 'Amina',
                'last_name' => 'Hassan',
                'phone' => '+255712345678',
                'nationality' => 'TZ',
                'vip_level' => 1,
            ]
        );
        if ($guestA->trashed()) {
            $guestA->restore();
        }

        $guestB = Guest::withTrashed()->updateOrCreate(
            ['email' => 'james.okello@example.com'],
            [
                'first_name' => 'James',
                'last_name' => 'Okello',
                'phone' => '+255798765432',
                'nationality' => 'KE',
            ]
        );
        if ($guestB->trashed()) {
            $guestB->restore();
        }

        $room103 = Room::query()->where('room_number', '103')->first();

        $reservationA = Reservation::withTrashed()->updateOrCreate(
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
        if ($reservationA->trashed()) {
            $reservationA->restore();
        }

        $reservationB = Reservation::withTrashed()->updateOrCreate(
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
        if ($reservationB->trashed()) {
            $reservationB->restore();
        }

        unset($reservationA, $reservationB);
    }
}
