<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Models\FolioTransaction;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Services\FolioService;
use App\Domain\Shared\Services\ReportingService;
use Tests\TenantTestCase;

class ReportingTest extends TenantTestCase
{
    public function test_fb_revenue_split_groups_food_and_drinks(): void
    {
        $reservation = Reservation::factory()->create(['room_type_id' => RoomType::factory()->create()->id]);
        $folio = app(FolioService::class)->openFolio($reservation);

        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'restaurant',
            'description' => 'Dinner',
            'amount' => 50000,
            'tax_amount' => 9000,
            'posted_at' => now(),
        ]);
        FolioTransaction::query()->create([
            'folio_id' => $folio->id,
            'transaction_type' => 'bar',
            'description' => 'Drinks',
            'amount' => 30000,
            'tax_amount' => 5400,
            'posted_at' => now(),
        ]);

        $split = app(ReportingService::class)->fbRevenueSplit(now()->startOfDay(), now()->endOfDay());

        $this->assertEquals('50000', $split['food']);
        $this->assertEquals('30000', $split['drinks']);
    }
}
