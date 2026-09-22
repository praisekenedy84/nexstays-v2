<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\RoomType;
use App\Domain\Shared\Actions\SyncFeatureCeiling;
use App\Domain\Shared\Models\SalesSnapshot;
use App\Domain\Shared\Services\DivisionSalesService;
use App\Models\Tenant;
use Carbon\Carbon;
use Tests\TenantTestCase;

class DashboardOverviewTest extends TenantTestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => []]);

        $this->tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $this->tenant->enabled_features = null;
        $this->tenant->save();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_booked_room_revenue_includes_confirmed_reservations(): void
    {
        $roomType = RoomType::factory()->create();

        Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'confirmed',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'daily_rate' => '100000.00',
        ]);

        Reservation::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => 'inquiry',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'daily_rate' => '50000.00',
        ]);

        $todayBooked = app(DivisionSalesService::class)->todayArrivalBookedRevenue();
        $mtdBooked = app(DivisionSalesService::class)->bookedRoomRevenue(
            now()->startOfMonth(),
            now()->endOfDay(),
        );

        $this->assertEquals(200000.0, $todayBooked['revenue']);
        $this->assertEquals(1, $todayBooked['reservation_count']);
        $this->assertEquals(200000.0, $mtdBooked['revenue']);
    }

    public function test_revenue_trend_includes_snapshots_and_today_live_point(): void
    {
        SalesSnapshot::query()->create([
            'snapshot_date' => now()->subDays(2)->toDateString(),
            'rooms' => '100000.00',
            'restaurant' => '20000.00',
            'bar' => '10000.00',
            'ancillary' => '5000.00',
            'total' => '135000.00',
            'room_nights' => 5,
            'payments_collected' => '80000.00',
        ]);

        SalesSnapshot::query()->create([
            'snapshot_date' => now()->subDay()->toDateString(),
            'rooms' => '120000.00',
            'restaurant' => '25000.00',
            'bar' => '15000.00',
            'ancillary' => '0.00',
            'total' => '160000.00',
            'room_nights' => 6,
            'payments_collected' => '90000.00',
        ]);

        $from = now()->subDays(29)->startOfDay();
        $to = now()->startOfDay();
        $service = app(DivisionSalesService::class);
        $trend = $service->revenueTrend($from, $to);

        $this->assertCount(30, $trend);
        $this->assertFalse($service->revenueTrendIsHourly($from, $to));
        $this->assertSame(now()->subDays(2)->toDateString(), $trend[27]['date']);
        $this->assertSame(135000.0, $trend[27]['total']);
        $this->assertSame(now()->subDay()->toDateString(), $trend[28]['date']);
        $this->assertSame(160000.0, $trend[28]['total']);
        $this->assertTrue($trend[29]['is_live']);
        $this->assertSame(now()->toDateString(), $trend[29]['date']);
    }

    public function test_revenue_trend_returns_hourly_buckets_for_single_day(): void
    {
        $today = Carbon::today();
        $service = app(DivisionSalesService::class);
        $trend = $service->revenueTrend($today, $today);

        $this->assertTrue($service->revenueTrendIsHourly($today, $today));
        $this->assertCount(24, $trend);
        $this->assertSame('00:00', $trend[0]['label']);
        $this->assertSame('23:00', $trend[23]['label']);
        $this->assertSame('hour', $trend[0]['granularity']);
        $this->assertTrue($trend[(int) now()->format('G')]['is_live']);
    }

    public function test_revenue_trend_hourly_for_past_day_has_no_live_point(): void
    {
        $yesterday = Carbon::yesterday();
        $service = app(DivisionSalesService::class);
        $trend = $service->revenueTrend($yesterday, $yesterday);

        $this->assertCount(24, $trend);
        $this->assertFalse(collect($trend)->contains(fn (array $point) => $point['is_live']));
    }

    public function test_revenue_trend_today_respects_application_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-08 01:30:00', 'Africa/Dar_es_Salaam'));
        config(['app.timezone' => 'Africa/Dar_es_Salaam']);
        date_default_timezone_set('Africa/Dar_es_Salaam');

        $today = now()->startOfDay();
        $trend = app(DivisionSalesService::class)->revenueTrend($today, $today);

        $this->assertSame('2026-06-08', $today->toDateString());
        $this->assertTrue(collect($trend)->contains(
            fn (array $point) => $point['is_live'] && str_starts_with($point['date'], '2026-06-08')
        ));

        Carbon::setTestNow();
    }

    public function test_dashboard_shows_both_summaries_when_hotel_and_restaurant_enabled(): void
    {
        $this->tenant->enabled_features = ['hotel', 'restaurant'];
        $this->tenant->save();
        tenancy()->initialize($this->tenant);
        app(SyncFeatureCeiling::class)->execute($this->tenant);

        $this->user->refresh();
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Hotel summary', false)
            ->assertSee('Restaurant / F&amp;B summary', false)
            ->assertSee('Hotel revenue', false)
            ->assertSee('All F&amp;B revenue', false)
            ->assertSee('All F&amp;B', false)
            ->assertSee('Restaurant', false)
            ->assertSee('Bar &amp; lounge', false);
    }

    public function test_dashboard_shows_only_hotel_summary_when_restaurant_disabled(): void
    {
        $this->tenant->enabled_features = ['hotel'];
        $this->tenant->save();
        tenancy()->initialize($this->tenant);
        app(SyncFeatureCeiling::class)->execute($this->tenant);

        $this->user->refresh();
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Hotel summary', false)
            ->assertDontSee('Restaurant / F&amp;B summary', false)
            ->assertDontSee('All F&amp;B revenue', false);
    }

    public function test_dashboard_shows_only_fb_summary_when_hotel_disabled(): void
    {
        $this->tenant->enabled_features = ['restaurant', 'bar', 'lounge'];
        $this->tenant->save();
        tenancy()->initialize($this->tenant);
        app(SyncFeatureCeiling::class)->execute($this->tenant);

        $this->user->refresh();
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertDontSee('Hotel summary', false)
            ->assertSee('Restaurant / F&amp;B summary', false)
            ->assertSee('All F&amp;B revenue', false)
            ->assertDontSee('Today arrivals', false);
    }

    public function test_dashboard_fb_scope_filter_switches_restaurant_and_bar(): void
    {
        $this->tenant->enabled_features = ['hotel', 'restaurant', 'bar'];
        $this->tenant->save();
        tenancy()->initialize($this->tenant);
        app(SyncFeatureCeiling::class)->execute($this->tenant);

        $this->user->refresh();
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.dashboard', ['fb' => 'restaurant']))
            ->assertOk()
            ->assertSee('Restaurant revenue', false)
            ->assertDontSee('All F&amp;B revenue', false)
            ->assertSee('fb=bar', false);

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.dashboard', ['fb' => 'bar']))
            ->assertOk()
            ->assertSee('Bar &amp; lounge revenue', false)
            ->assertDontSee('Restaurant today', false);
    }
}
