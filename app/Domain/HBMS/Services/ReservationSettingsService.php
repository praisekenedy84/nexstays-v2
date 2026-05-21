<?php

declare(strict_types=1);

namespace App\Domain\HBMS\Services;

use App\Models\Tenant;

class ReservationSettingsService
{
    /**
     * @return array{
     *     payment_mode: string,
     *     cancellation: array{policy: string, refund_percentage: float}
     * }
     */
    public function all(): array
    {
        $defaults = $this->defaults();
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return $defaults;
        }

        $stored = data_get($tenant, 'data.reservation_settings', []);
        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'payment_mode' => (string) ($stored['payment_mode'] ?? $defaults['payment_mode']),
            'cancellation' => [
                'policy' => (string) ($stored['cancellation']['policy'] ?? $defaults['cancellation']['policy']),
                'refund_percentage' => (float) ($stored['cancellation']['refund_percentage'] ?? $defaults['cancellation']['refund_percentage']),
            ],
        ];
    }

    /**
     * @param array{
     *     payment_mode: string,
     *     cancellation: array{policy: string, refund_percentage: float}
     * } $settings
     */
    public function update(array $settings): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $data = is_array($tenant->data ?? null) ? $tenant->data : [];
        $data['reservation_settings'] = [
            'payment_mode' => $settings['payment_mode'],
            'cancellation' => [
                'policy' => $settings['cancellation']['policy'],
                'refund_percentage' => $settings['cancellation']['refund_percentage'],
            ],
        ];

        $tenant->update(['data' => $data]);
    }

    /**
     * @return array{
     *     payment_mode: string,
     *     cancellation: array{policy: string, refund_percentage: float}
     * }
     */
    public function defaults(): array
    {
        return [
            'payment_mode' => (string) config('nexstay.reservations.payment_mode', 'prepaid'),
            'cancellation' => [
                'policy' => (string) config('nexstay.reservations.cancellation.policy', 'stayed_nights'),
                'refund_percentage' => (float) config('nexstay.reservations.cancellation.refund_percentage', 0),
            ],
        ];
    }
}
