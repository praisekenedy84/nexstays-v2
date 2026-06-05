<?php

declare(strict_types=1);

namespace App\Support;

final class Username
{
    public const PATTERN = '/^[a-z0-9_.-]+$/';

    public static function deriveFromEmail(?string $email, ?string $name = null): string
    {
        if ($email !== null && $email !== '' && str_contains($email, '@')) {
            $local = explode('@', $email, 2)[0];

            return self::sanitize($local);
        }

        if ($name !== null && $name !== '') {
            return self::sanitize(str_replace(' ', '_', strtolower($name)));
        }

        return 'user';
    }

    public static function sanitize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_.-]/', '', $value) ?? '';

        return $value !== '' ? $value : 'user';
    }

    /**
     * @param  list<string>  $used
     */
    public static function ensureUnique(string $base, array &$used): string
    {
        $candidate = $base;
        $suffix = 2;

        while (in_array($candidate, $used, true)) {
            $candidate = $base.'_'.$suffix;
            $suffix++;
        }

        $used[] = $candidate;

        return $candidate;
    }
}
