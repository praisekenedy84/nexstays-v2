<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'hidden_navigation_ids',
    ];

    protected function casts(): array
    {
        return [
            'hidden_navigation_ids' => 'array',
        ];
    }
}
