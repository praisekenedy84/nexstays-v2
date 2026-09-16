<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Collection;

final class TenantFeatures
{
    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(config('nexstay.features', []));
    }

    /**
     * @return array<string, array{label: string, description: string, permissions: list<string>}>
     */
    public static function definitions(): array
    {
        return config('nexstay.features', []);
    }

    /**
     * Enabled module keys for a tenant. Null/unset → all keys (backward compatible).
     *
     * @return list<string>
     */
    public static function enabled(?Tenant $tenant = null): array
    {
        $tenant ??= tenant();

        if ($tenant === null) {
            return self::keys();
        }

        $raw = $tenant->enabled_features;

        if ($raw === null) {
            return self::keys();
        }

        return array_values(array_intersect(self::keys(), array_map('strval', (array) $raw)));
    }

    public static function allows(string $feature, ?Tenant $tenant = null): bool
    {
        return in_array($feature, self::enabled($tenant), true);
    }

    /**
     * True when any of the given feature keys is enabled.
     *
     * @param  list<string>  $features
     */
    public static function allowsAny(array $features, ?Tenant $tenant = null): bool
    {
        if ($features === []) {
            return true;
        }

        $enabled = self::enabled($tenant);

        foreach ($features as $feature) {
            if (in_array($feature, $enabled, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function allowedPermissions(?Tenant $tenant = null): array
    {
        $enabled = self::enabled($tenant);
        $permissions = config('nexstay.always_on_permissions', []);

        foreach ($enabled as $key) {
            $permissions = array_merge(
                $permissions,
                config("nexstay.features.{$key}.permissions", [])
            );
        }

        $fbKeys = config('nexstay.fb_feature_keys', ['restaurant', 'bar', 'lounge']);
        if (array_intersect($enabled, $fbKeys) !== []) {
            $permissions = array_merge($permissions, config('nexstay.shared_fb_permissions', []));
        }

        return array_values(array_unique($permissions));
    }

    public static function allowsPermission(string $permission, ?Tenant $tenant = null): bool
    {
        return in_array($permission, self::allowedPermissions($tenant), true);
    }

    /**
     * Whether a navigation config item is allowed by feature packs.
     *
     * @param  array{feature?: string, features?: list<string>}  $item
     */
    public static function allowsNavigationItem(array $item, ?Tenant $tenant = null): bool
    {
        if (isset($item['feature'])) {
            return self::allows((string) $item['feature'], $tenant);
        }

        if (isset($item['features']) && is_array($item['features'])) {
            return self::allowsAny($item['features'], $tenant);
        }

        return true;
    }

    public static function allowsNavigationId(string $id, ?Tenant $tenant = null): bool
    {
        foreach (self::navigationItemsFlat() as $item) {
            if ($item['id'] === $id) {
                return self::allowsNavigationItem($item, $tenant);
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public static function allowedNavigationIds(?Tenant $tenant = null): array
    {
        return Collection::make(self::navigationItemsFlat())
            ->filter(fn (array $item) => self::allowsNavigationItem($item, $tenant))
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * Flatten navigation leaves (and top-level links) with feature metadata.
     *
     * @return list<array{id: string, feature?: string, features?: list<string>}>
     */
    private static function navigationItemsFlat(): array
    {
        $items = [];

        foreach (config('nexstay.navigation', []) as $item) {
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    $items[] = $child;
                }

                continue;
            }

            $items[] = $item;
        }

        return $items;
    }
}
