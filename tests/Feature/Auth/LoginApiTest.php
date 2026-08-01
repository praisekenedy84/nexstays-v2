<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;

class LoginApiTest extends TenantTestCase
{
    public function test_login_rejects_suspended_tenant(): void
    {
        config(['tenancy.bootstrappers' => []]);

        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = now();
        $tenant->save();

        User::factory()->create(['username' => 'frontdesk', 'email' => 'staff@demo.local']);

        $this->tenantJson('POST', '/api/v1/auth/login', [
            'property_code' => 'demo',
            'username' => 'frontdesk',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_code']);
    }

    public function test_it_rejects_invalid_credentials(): void
    {
        config(['tenancy.bootstrappers' => []]);

        Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        User::factory()->create(['username' => 'frontdesk', 'email' => 'staff@demo.local']);

        $this->tenantJson('POST', '/api/v1/auth/login', [
            'property_code' => 'demo',
            'username' => 'frontdesk',
            'password' => 'wrong',
        ])->assertUnprocessable();
    }

    public function test_login_accepts_email_instead_of_username(): void
    {
        config(['tenancy.bootstrappers' => []]);

        Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        User::factory()->create([
            'username' => 'frontdesk',
            'email' => 'staff@demo.local',
            'password' => Hash::make('secret-pass'),
        ]);

        $this->tenantJson('POST', '/api/v1/auth/login', [
            'property_code' => 'demo',
            'email' => 'staff@demo.local',
            'password' => 'wrong',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);
    }

    public function test_login_accepts_username_in_username_field(): void
    {
        config(['tenancy.bootstrappers' => []]);

        Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        User::factory()->create([
            'username' => 'frontdesk',
            'email' => 'staff@demo.local',
            'password' => Hash::make('secret-pass'),
        ]);

        $this->tenantJson('POST', '/api/v1/auth/login', [
            'property_code' => 'demo',
            'username' => 'frontdesk',
            'password' => 'wrong',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);
    }

    public function test_login_requires_username_or_email(): void
    {
        $this->tenantJson('POST', '/api/v1/auth/login', [
            'property_code' => 'demo',
            'password' => 'secret-pass',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'email']);
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
            ->assertJsonPath('data.attributes.username', $this->user->username);
    }

    public function test_login_is_throttled_after_too_many_attempts(): void
    {
        config(['tenancy.bootstrappers' => []]);

        Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        User::factory()->create(['username' => 'frontdesk', 'email' => 'staff@demo.local']);

        $payload = [
            'property_code' => 'demo',
            'username' => 'frontdesk',
            'password' => 'wrong',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->tenantJson('POST', '/api/v1/auth/login', $payload)->assertUnprocessable();
        }

        $this->tenantJson('POST', '/api/v1/auth/login', $payload)->assertStatus(429);
    }
}
