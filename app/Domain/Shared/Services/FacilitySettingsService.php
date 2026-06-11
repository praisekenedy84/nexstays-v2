<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\Tenant;

class FacilitySettingsService
{
    private const KEY = 'facility_settings';

    /** @return array{pool_default_fee: float, gym_default_fee: float} */
    public function all(): array
    {
        $defaults = $this->defaults();
        $tenant   = tenant();

        if (! $tenant instanceof Tenant) {
            return $defaults;
        }

        $stored = $tenant->{self::KEY} ?? null;

        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'pool_default_fee' => is_numeric($stored['pool_default_fee'] ?? null)
                ? (float) $stored['pool_default_fee']
                : $defaults['pool_default_fee'],
            'gym_default_fee' => is_numeric($stored['gym_default_fee'] ?? null)
                ? (float) $stored['gym_default_fee']
                : $defaults['gym_default_fee'],
        ];
    }

    public function defaultFeeFor(string $facilityType): float
    {
        return match ($facilityType) {
            'gym'   => $this->all()['gym_default_fee'],
            default => $this->all()['pool_default_fee'],
        };
    }

    /** @param array{pool_default_fee: float|int|string, gym_default_fee: float|int|string} $settings */
    public function update(array $settings): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $tenant->{self::KEY} = [
            'pool_default_fee' => (float) $settings['pool_default_fee'],
            'gym_default_fee'  => (float) $settings['gym_default_fee'],
        ];

        $tenant->save();
    }

    /** @return array{pool_default_fee: float, gym_default_fee: float} */
    public function defaults(): array
    {
        return [
            'pool_default_fee' => (float) config('nexstay.facilities.pool.default_fee', 0),
            'gym_default_fee'  => (float) config('nexstay.facilities.gym.default_fee', 0),
        ];
    }
}
