<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Username;
use Tests\TenantTestCase;

class UsernameLoginTest extends TenantTestCase
{
    public function test_migration_backfills_username_from_email(): void
    {
        $user = User::factory()->create([
            'username' => 'manager',
            'email' => 'manager@demo.local',
        ]);

        $this->assertSame('manager', Username::deriveFromEmail('manager@demo.local'));
        $this->assertSame('manager', $user->username);
    }
}
