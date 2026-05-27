<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

final class HbmsNavigation
{
    /**
     * Navigation tree: top-level items are links or groups with filtered children.
     *
     * @return list<array<string, mixed>>
     */
    public static function forUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return Collection::make(config('nexstay.navigation', []))
            ->map(fn (array $item) => self::resolveItem($item, $user))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function resolveItem(array $item, User $user): ?array
    {
        if (isset($item['children'])) {
            $children = Collection::make($item['children'])
                ->filter(fn (array $child) => self::canSee($child, $user))
                ->values()
                ->all();

            if ($children === []) {
                return null;
            }

            return [
                'id' => $item['id'],
                'label' => $item['label'],
                'icon' => $item['icon'] ?? 'chart',
                'children' => $children,
            ];
        }

        if (! self::canSee($item, $user)) {
            return null;
        }

        return [
            'id' => $item['id'],
            'label' => $item['label'],
            'route' => $item['route'],
            'icon' => $item['icon'] ?? 'chart',
        ];
    }

    /**
     * @param  array{permission?: string|null}  $item
     */
    private static function canSee(array $item, User $user): bool
    {
        $permission = $item['permission'] ?? null;

        return $permission === null || $user->can($permission);
    }

    public static function tenantHomeUrl(): string
    {
        return config('app.url', 'http://localhost:8000').'/login';
    }
}
