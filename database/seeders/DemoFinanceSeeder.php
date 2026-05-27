<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Expenditures\Models\Expenditure;
use App\Domain\HBMS\Models\AncillaryService;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', config('nexstay.demo.admin_email'))->first();

        $services = [
            ['name' => 'Airport transfer', 'default_price' => 45000, 'description' => 'One-way airport pickup'],
            ['name' => 'Laundry (per kg)', 'default_price' => 8000, 'description' => 'Guest laundry service'],
            ['name' => 'Late checkout', 'default_price' => 25000, 'description' => 'Until 6 PM subject to availability'],
            ['name' => 'Extra bed', 'default_price' => 35000, 'description' => 'Rollaway bed per night'],
        ];

        foreach ($services as $i => $service) {
            $ancillary = AncillaryService::withTrashed()->updateOrCreate(
                ['name' => $service['name']],
                [
                    ...$service,
                    'currency' => config('nexstay.currency.default', 'TZS'),
                    'is_active' => true,
                    'sort_order' => $i,
                ]
            );
            if ($ancillary->trashed()) {
                $ancillary->restore();
            }
        }

        if ($admin) {
            $expenditure = Expenditure::withTrashed()->updateOrCreate(
                ['description' => 'Generator diesel — May'],
                [
                    'category' => 'utilities',
                    'amount' => 850000,
                    'currency' => 'TZS',
                    'expense_date' => now()->startOfMonth(),
                    'recorded_by' => $admin->id,
                ]
            );
            if ($expenditure->trashed()) {
                $expenditure->restore();
            }
        }
    }
}
