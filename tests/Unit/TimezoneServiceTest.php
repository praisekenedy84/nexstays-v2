<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Shared\Services\TimezoneService;
use App\Models\User;
use Tests\TestCase;

class TimezoneServiceTest extends TestCase
{
    public function test_normalize_maps_gmt_plus_three_alias_to_dar_es_salaam(): void
    {
        $service = app(TimezoneService::class);

        $this->assertSame('Africa/Dar_es_Salaam', $service->normalize('GMT+3'));
        $this->assertSame('Africa/Dar_es_Salaam', $service->normalize('UTC+3'));
    }

    public function test_resolve_prefers_user_timezone_over_configured_default(): void
    {
        config()->set('nexstay.timezone.default', 'UTC');

        $user = new User(['timezone' => 'America/New_York']);

        $this->assertSame('America/New_York', app(TimezoneService::class)->resolve($user));
    }

    public function test_resolve_falls_back_to_gmt_plus_three_when_unknown(): void
    {
        config()->set('nexstay.timezone.default', 'Not/A/Timezone');

        $this->assertSame('Africa/Dar_es_Salaam', app(TimezoneService::class)->resolve());
    }

    public function test_apply_sets_runtime_timezone(): void
    {
        $user = new User(['timezone' => 'Africa/Nairobi']);

        $applied = app(TimezoneService::class)->apply($user);

        $this->assertSame('Africa/Nairobi', $applied);
        $this->assertSame('Africa/Nairobi', config('app.timezone'));
        $this->assertSame('Africa/Nairobi', date_default_timezone_get());
    }
}
