<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\Tenant;

class FbSettingsService
{
    private const KEY = 'fb_settings';

    /** @var array<string, string> */
    public const SETTLEMENT_MODES = [
        'both'   => 'Both — waiter can post to folio OR collect directly',
        'direct' => 'Direct only — cash, mobile money, or card (no folio)',
        'folio'  => 'Folio only — post to guest room account',
    ];

    /** @return array{settlement_mode: string} */
    public function all(): array
    {
        $defaults = $this->defaults();
        $tenant   = tenant();

        if (! $tenant instanceof Tenant) {
            return $defaults;
        }

        $stored = data_get($tenant, 'data.' . self::KEY, null);

        if (! is_array($stored)) {
            return $defaults;
        }

        $mode = $stored['settlement_mode'] ?? $defaults['settlement_mode'];

        return [
            'settlement_mode' => array_key_exists($mode, self::SETTLEMENT_MODES)
                ? $mode
                : $defaults['settlement_mode'],
        ];
    }

    public function settlementMode(): string
    {
        return $this->all()['settlement_mode'];
    }

    public function allowsDirect(): bool
    {
        return in_array($this->settlementMode(), ['both', 'direct'], true);
    }

    public function allowsFolio(): bool
    {
        return in_array($this->settlementMode(), ['both', 'folio'], true);
    }

    /** @param array{settlement_mode: string} $settings */
    public function update(array $settings): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $data            = is_array($tenant->data ?? null) ? $tenant->data : [];
        $data[self::KEY] = [
            'settlement_mode' => array_key_exists($settings['settlement_mode'], self::SETTLEMENT_MODES)
                ? $settings['settlement_mode']
                : 'both',
        ];

        $tenant->update(['data' => $data]);
    }

    /** @return array{settlement_mode: string} */
    public function defaults(): array
    {
        return ['settlement_mode' => 'both'];
    }
}
