<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\Tenant;
use Carbon\CarbonInterface;

class ReportDeliverySettingsService
{
    /**
     * @return array{
     *     recipient_email: string,
     *     send_time: string,
     *     timezone: string,
     *     last_sent_on: string|null
     * }
     */
    public function all(): array
    {
        $defaults = $this->defaults();
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return $defaults;
        }

        $stored = data_get($tenant, 'data.report_delivery_settings', []);
        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'recipient_email' => (string) ($stored['recipient_email'] ?? $defaults['recipient_email']),
            'send_time' => (string) ($stored['send_time'] ?? $defaults['send_time']),
            'timezone' => (string) ($stored['timezone'] ?? $defaults['timezone']),
            'last_sent_on' => isset($stored['last_sent_on']) ? (string) $stored['last_sent_on'] : null,
        ];
    }

    /**
     * @param array{
     *     recipient_email: string,
     *     send_time: string,
     *     timezone: string
     * } $settings
     */
    public function update(array $settings): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $current = $this->all();
        $data = is_array($tenant->data ?? null) ? $tenant->data : [];
        $data['report_delivery_settings'] = [
            'recipient_email' => strtolower(trim($settings['recipient_email'])),
            'send_time' => $settings['send_time'],
            'timezone' => $settings['timezone'],
            'last_sent_on' => $current['last_sent_on'],
        ];

        $tenant->update(['data' => $data]);
    }

    public function dueForDispatch(CarbonInterface $moment): bool
    {
        $settings = $this->all();

        if ($settings['recipient_email'] === '') {
            return false;
        }

        $nowInTimezone = $moment->copy()->setTimezone($settings['timezone']);
        if ($nowInTimezone->format('H:i') < $settings['send_time']) {
            return false;
        }

        return $settings['last_sent_on'] !== $nowInTimezone->toDateString();
    }

    public function markSent(CarbonInterface $sentAt): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $settings = $this->all();
        $data = is_array($tenant->data ?? null) ? $tenant->data : [];
        $data['report_delivery_settings'] = [
            'recipient_email' => $settings['recipient_email'],
            'send_time' => $settings['send_time'],
            'timezone' => $settings['timezone'],
            'last_sent_on' => $sentAt->copy()->setTimezone($settings['timezone'])->toDateString(),
        ];

        $tenant->update(['data' => $data]);
    }

    /**
     * @return array{
     *     recipient_email: string,
     *     send_time: string,
     *     timezone: string,
     *     last_sent_on: string|null
     * }
     */
    public function defaults(): array
    {
        return [
            'recipient_email' => (string) config('nexstay.reports.delivery.recipient_email', ''),
            'send_time' => (string) config('nexstay.reports.delivery.send_time', '08:00'),
            'timezone' => (string) config('nexstay.reports.delivery.timezone', config('app.timezone', 'UTC')),
            'last_sent_on' => null,
        ];
    }
}
