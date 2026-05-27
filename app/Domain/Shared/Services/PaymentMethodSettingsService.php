<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\Tenant;

class PaymentMethodSettingsService
{
    private const KEY = 'payment_method_settings';

    public const METHODS = [
        'cash' => [
            'label'       => 'Cash',
            'description' => 'Physical banknotes and coins.',
        ],
        'card' => [
            'label'       => 'Card',
            'description' => 'Credit or debit card via a POS terminal.',
        ],
        'mobile_money' => [
            'label'       => 'Mobile money',
            'description' => 'Mobile payment (M-Pesa, Airtel Money, etc.).',
        ],
    ];

    /** @return array{enabled: list<string>} */
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

        $enabled = is_array($stored['enabled'] ?? null) ? $stored['enabled'] : $defaults['enabled'];

        return [
            'enabled' => array_values(array_filter(
                $enabled,
                static fn (mixed $m) => is_string($m) && isset(self::METHODS[$m])
            )),
        ];
    }

    /** @return list<string> */
    public function enabledMethods(): array
    {
        return $this->all()['enabled'];
    }

    public function isEnabled(string $method): bool
    {
        return in_array($method, $this->enabledMethods(), true);
    }

    /** @param list<string> $enabled */
    public function update(array $enabled): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $data             = is_array($tenant->data ?? null) ? $tenant->data : [];
        $data[self::KEY]  = [
            'enabled' => array_values(array_filter(
                $enabled,
                static fn (mixed $m) => is_string($m) && isset(self::METHODS[$m])
            )),
        ];

        $tenant->update(['data' => $data]);
    }

    /** @return array{enabled: list<string>} */
    public function defaults(): array
    {
        return ['enabled' => array_keys(self::METHODS)];
    }
}
