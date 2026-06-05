<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Collection;

final class NavigationMenuRegistry
{
    /**
     * @return list<array{id: string, label: string, items: list<array{id: string, label: string, permission: string|null}>}>
     */
    public static function groups(): array
    {
        return Collection::make(config('nexstay.navigation', []))
            ->map(function (array $item): array {
                if (isset($item['children'])) {
                    return [
                        'id' => $item['id'],
                        'label' => $item['label'],
                        'items' => Collection::make($item['children'])
                            ->map(fn (array $child) => self::normalizeItem($child))
                            ->values()
                            ->all(),
                    ];
                }

                return [
                    'id' => $item['id'],
                    'label' => $item['label'],
                    'items' => [self::normalizeItem($item)],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, label: string, permission: string|null}>
     */
    public static function allItems(): array
    {
        return Collection::make(self::groups())
            ->flatMap(fn (array $group) => $group['items'])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function allItemIds(): array
    {
        return array_column(self::allItems(), 'id');
    }

    /**
     * @param  array{id: string, permission?: string|null}  $item
     */
    public static function isVisibleForRole(array $item, Role $role): bool
    {
        $permission = $item['permission'] ?? null;

        if ($permission !== null && ! $role->hasPermissionTo($permission)) {
            return false;
        }

        return ! in_array($item['id'], $role->hidden_navigation_ids ?? [], true);
    }

    /**
     * @return list<string>
     */
    public static function visibleItemIdsForRole(Role $role): array
    {
        return Collection::make(self::allItems())
            ->filter(fn (array $item) => self::isVisibleForRole($item, $role))
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * @param  array{id: string, label: string, route?: string, permission?: string|null}  $item
     * @return array{id: string, label: string, permission: string|null}
     */
    private static function normalizeItem(array $item): array
    {
        return [
            'id' => $item['id'],
            'label' => $item['label'],
            'permission' => $item['permission'] ?? null,
        ];
    }
}
