<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\Tenant;
use App\Models\User;
use DateTimeZone;
use Exception;

class TimezoneService
{
    public const FALLBACK = 'Africa/Dar_es_Salaam';

    /** @var array<string, string> */
    private const ALIASES = [
        'UTC+3' => self::FALLBACK,
        'GMT+3' => self::FALLBACK,
        'EAT' => self::FALLBACK,
    ];

    public function normalize(string $timezone): string
    {
        $timezone = trim($timezone);

        if ($timezone === '') {
            return $this->fallback();
        }

        $alias = self::ALIASES[strtoupper($timezone)] ?? null;
        if ($alias !== null) {
            return $alias;
        }

        try {
            new DateTimeZone($timezone);

            return $timezone;
        } catch (Exception) {
            return $this->fallback();
        }
    }

    public function fallback(): string
    {
        $configured = config('nexstay.timezone.fallback');

        return is_string($configured) && $configured !== ''
            ? $this->normalize($configured)
            : self::FALLBACK;
    }

    public function resolve(?User $user = null, ?Tenant $tenant = null): string
    {
        if ($user?->timezone) {
            return $this->normalize((string) $user->timezone);
        }

        $tenant ??= tenant();
        if ($tenant instanceof Tenant) {
            $stored = $tenant->timezone ?? data_get($tenant, 'data.timezone');
            if (is_string($stored) && $stored !== '') {
                return $this->normalize($stored);
            }
        }

        $configured = config('nexstay.timezone.default', config('app.timezone'));
        if (is_string($configured) && $configured !== '') {
            return $this->normalize($configured);
        }

        return $this->fallback();
    }

    public function apply(?User $user = null, ?Tenant $tenant = null): string
    {
        $timezone = $this->resolve($user, $tenant);
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        return $timezone;
    }

    public function syncForUser(User $user, string $timezone, ?Tenant $tenant = null): string
    {
        $timezone = $this->normalize($timezone);

        if ($user->timezone !== $timezone) {
            $user->forceFill(['timezone' => $timezone])->save();
        }

        $this->seedTenantTimezone($timezone, $tenant);

        return $timezone;
    }

    private function seedTenantTimezone(string $timezone, ?Tenant $tenant = null): void
    {
        $tenant ??= tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        if (! empty($tenant->timezone)) {
            return;
        }

        $tenant->timezone = $timezone;
        $tenant->save();
    }
}
