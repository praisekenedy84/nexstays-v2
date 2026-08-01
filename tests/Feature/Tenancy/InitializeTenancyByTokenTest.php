<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\InitializeTenancyByToken;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TenantTestCase;

class InitializeTenancyByTokenTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => []]);

        // The central personal_access_tokens table lives on its own connection
        // and isn't covered by the default-connection RefreshDatabase migration.
        // Build it (and its FK target) directly so each test starts from a clean slate.
        Schema::connection('central')->dropIfExists('personal_access_tokens');
        Schema::connection('central')->dropIfExists('tenants');

        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->json('data')->nullable();
        });

        Schema::connection('central')->create('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->uuidMorphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }

    /**
     * The tenants table the PAT migration FK-references lives on the central
     * connection; mirror the row created on the default connection there too.
     */
    private function mirrorTenantToCentral(Tenant $tenant): void
    {
        DB::connection('central')->table('tenants')->insert([
            'id' => $tenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_expired_token_does_not_initialize_tenancy(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $this->mirrorTenantToCentral($tenant);

        $token = $this->user->createToken('api');

        PersonalAccessToken::query()
            ->where('id', $token->accessToken->id)
            ->update([
                'tenant_id' => $tenant->id,
                'expires_at' => now()->subMinute(),
            ]);

        $request = Request::create('/api/v1/auth/me', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$token->plainTextToken);

        (new InitializeTenancyByToken())->handle($request, fn ($req) => response('ok'));

        $this->assertFalse(tenancy()->initialized);
    }

    public function test_valid_token_initializes_tenancy_for_its_tenant(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $this->mirrorTenantToCentral($tenant);

        $token = $this->user->createToken('api');

        PersonalAccessToken::query()
            ->where('id', $token->accessToken->id)
            ->update(['tenant_id' => $tenant->id]);

        $request = Request::create('/api/v1/auth/me', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$token->plainTextToken);

        (new InitializeTenancyByToken())->handle($request, fn ($req) => response('ok'));

        $this->assertTrue(tenancy()->initialized);
        $this->assertSame($tenant->id, tenant('id'));

        tenancy()->end();
    }

    public function test_suspended_tenant_via_header_returns_forbidden_with_reason(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = now();
        $tenant->suspension_reason = 'Contract ended';
        $tenant->save();

        $request = Request::create('/api/v1/availability', 'GET');
        $request->headers->set('X-Tenant-Id', $tenant->id);

        $response = (new InitializeTenancyByToken())->handle($request, fn ($req) => response('ok'));

        $this->assertSame(403, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('TENANT_SUSPENDED', $payload['error']['code'] ?? null);
        $this->assertSame('Contract ended', $payload['error']['details']['reason'] ?? null);
        $this->assertStringContainsString('Contract ended', $payload['error']['message'] ?? '');
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_suspended_tenant_via_header_returns_forbidden(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = now();
        $tenant->save();

        $request = Request::create('/api/v1/availability', 'GET');
        $request->headers->set('X-Tenant-Id', $tenant->id);

        $response = (new InitializeTenancyByToken())->handle($request, fn ($req) => response('ok'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('TENANT_SUSPENDED', $response->getData(true)['error']['code'] ?? null);
        $this->assertFalse(tenancy()->initialized);
    }
}
