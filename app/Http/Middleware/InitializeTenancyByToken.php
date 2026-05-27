<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId === null) {
            return $next($request);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json([
                'error' => [
                    'code' => 'TENANT_NOT_FOUND',
                    'message' => 'The property associated with this request could not be found.',
                    'details' => null,
                    'request_id' => (string) \Illuminate\Support\Str::uuid(),
                ],
            ], 401);
        }

        if (! tenancy()->initialized) {
            tenancy()->initialize($tenant);
        }

        return $next($request);
    }

    private function resolveTenantId(Request $request): ?string
    {
        // Primary: resolve tenant from the Bearer token's stored tenant_id.
        $plainText = $request->bearerToken();

        if ($plainText !== null) {
            $pat = PersonalAccessToken::where('token', hash('sha256', $plainText))->first();

            if ($pat && $pat->tenant_id) {
                return (string) $pat->tenant_id;
            }

            // Token present but no tenant_id → invalid token, let auth:sanctum reject it.
            return null;
        }

        // Fallback: explicit header for unauthenticated public endpoints (e.g. availability).
        $header = $request->header('X-Tenant-Id');

        if ($header) {
            return (string) $header;
        }

        return null;
    }
}
