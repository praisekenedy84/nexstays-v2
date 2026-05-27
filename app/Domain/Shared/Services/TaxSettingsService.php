<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\Tenant;

class TaxSettingsService
{
    private const KEY = 'finance_settings';

    /**
     * @return array{
     *     tax_inclusive: bool,
     *     vat_rate: string,
     *     tax_code: string,
     *     rates: array{room_charge: string, restaurant: string, bar: string}
     * }
     */
    public function all(): array
    {
        $defaults = $this->defaults();
        $tenant   = tenant();

        if (! $tenant instanceof Tenant) {
            return $defaults;
        }

        $stored = data_get($tenant, 'data.' . self::KEY, []);

        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'tax_inclusive' => (bool) ($stored['tax_inclusive'] ?? $defaults['tax_inclusive']),
            'vat_rate'      => (string) ($stored['vat_rate'] ?? $defaults['vat_rate']),
            'tax_code'      => (string) ($stored['tax_code'] ?? $defaults['tax_code']),
            'rates'         => [
                'room_charge' => (string) ($stored['rates']['room_charge'] ?? $defaults['rates']['room_charge']),
                'restaurant'  => (string) ($stored['rates']['restaurant'] ?? $defaults['rates']['restaurant']),
                'bar'         => (string) ($stored['rates']['bar'] ?? $defaults['rates']['bar']),
            ],
        ];
    }

    /**
     * @param array{
     *     tax_inclusive: bool,
     *     vat_rate: string,
     *     tax_code: string,
     *     rates: array{room_charge: string, restaurant: string, bar: string}
     * } $settings
     */
    public function update(array $settings): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $data = is_array($tenant->data ?? null) ? $tenant->data : [];
        $data[self::KEY] = [
            'tax_inclusive' => (bool) $settings['tax_inclusive'],
            'vat_rate'      => (string) $settings['vat_rate'],
            'tax_code'      => (string) $settings['tax_code'],
            'rates'         => [
                'room_charge' => (string) $settings['rates']['room_charge'],
                'restaurant'  => (string) $settings['rates']['restaurant'],
                'bar'         => (string) $settings['rates']['bar'],
            ],
        ];

        $tenant->update(['data' => $data]);
    }

    /**
     * @return array{
     *     tax_inclusive: bool,
     *     vat_rate: string,
     *     tax_code: string,
     *     rates: array{room_charge: string, restaurant: string, bar: string}
     * }
     */
    public function defaults(): array
    {
        $cfg = config('nexstay.tax.default_rates', []);

        return [
            'tax_inclusive' => (bool) ($cfg['_inclusive'] ?? true),
            'vat_rate'      => (string) ($cfg['_default'] ?? '0.18'),
            'tax_code'      => (string) ($cfg['_code'] ?? 'A'),
            'rates'         => [
                'room_charge' => (string) ($cfg['room_charge'] ?? '0.18'),
                'restaurant'  => (string) ($cfg['restaurant'] ?? '0.18'),
                'bar'         => (string) ($cfg['bar'] ?? '0.18'),
            ],
        ];
    }
}
