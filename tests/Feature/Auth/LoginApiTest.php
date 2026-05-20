<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;

class LoginApiTest extends TenantTestCase
{
    public function test_it_logs_in_and_returns_a_token(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@demo.local',
            'password' => Hash::make('secret-pass'),
        ]);
        $user->assignRole('front_desk');

        $response = $this->tenantJson('POST', '/api/v1/auth/login', [
            'email' => 'staff@demo.local',
            'password' => 'secret-pass',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.type', 'user')
            ->assertJsonStructure(['data' => ['token', 'token_type']]);
    }

    public function test_it_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'staff@demo.local']);

        $this->tenantJson('POST', '/api/v1/auth/login', [
            'email' => 'staff@demo.local',
            'password' => 'wrong',
        ])->assertUnprocessable();
    }

    public function test_me_requires_authentication(): void
    {
        auth()->guard('sanctum')->forgetUser();
        auth()->guard('web')->forgetUser();

        $this->tenantJson('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $this->tenantJson('GET', '/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.attributes.email', $this->user->email);
    }
}
