<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\PropertyNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notify(
        string $type,
        string $title,
        string $body,
        array $data = [],
    ): PropertyNotification {
        return PropertyNotification::query()->create([
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
            'data'  => $data ?: null,
        ]);
    }

    public function unreadCount(): int
    {
        return PropertyNotification::query()->whereNull('read_at')->count();
    }

    /**
     * @return Collection<int, PropertyNotification>
     */
    public function recent(int $limit = 10): Collection
    {
        return PropertyNotification::query()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markRead(PropertyNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllRead(): void
    {
        PropertyNotification::query()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
