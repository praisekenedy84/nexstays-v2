<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TenantTestCase;

class DualLoginTest extends TenantTestCase
{
    public function test_find_by_login_identifier_matches_username(): void
    {
        $user = User::factory()->create([
            'username' => 'frontdesk',
            'email' => 'staff@demo.local',
        ]);

        $this->assertTrue($user->is(User::findByLoginIdentifier('frontdesk')));
    }

    public function test_find_by_login_identifier_matches_email(): void
    {
        $user = User::factory()->create([
            'username' => 'frontdesk',
            'email' => 'staff@demo.local',
        ]);

        $this->assertTrue($user->is(User::findByLoginIdentifier('staff@demo.local')));
    }
}
