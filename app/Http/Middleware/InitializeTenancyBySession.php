<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InitializeTenancyBySession
{
    public function __construct(private readonly TenantResolver $tenantResolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->session()->get('tenant_id');

        if (! $tenantId) {
            // The "active session" cookie outlives the session's own data once the
            // session has been garbage-collected for inactivity, so its presence
            // here means the session expired rather than the user never logging in.
            if ($request->cookie('nexstay_active') !== null) {
                Cookie::queue(Cookie::forget('nexstay_active'));

                return redirect()->route('tenant.login')
                    ->withErrors(['property_code' => 'Your session has expired due to inactivity. Please log in again.']);
            }

            return redirect()->route('tenant.login');
        }

        $tenant = $this->tenantResolver->find($tenantId);

        if (! $tenant) {
            $request->session()->forget('tenant_id');

            if (tenancy()->initialized) {
                tenancy()->end();
            }

            return redirect()->route('tenant.login')
                ->withErrors(['property_code' => 'Your property could not be found. Please log in again.']);
        }

        if ($this->tenantResolver->isSuspended($tenant)) {
            $request->session()->forget('tenant_id');

            if (tenancy()->initialized) {
                tenancy()->end();
            }

            return redirect()->route('tenant.login')
                ->withErrors(['property_code' => 'This property has been suspended. Please contact NexStay support.']);
        }

        if (! tenancy()->initialized) {
            try {
                tenancy()->initialize($tenant);
            } catch (Throwable $e) {
                // The tenant row exists but its database/schema doesn't (e.g. it was
                // dropped or never provisioned). Without this, every request with this
                // session would keep crashing with a 500 until the user cleared cookies,
                // since the broken tenant_id never gets removed from the session.
                Log::error('Tenancy initialization failed for tenant from session.', [
                    'tenant_id' => $tenant->id,
                    'exception' => $e->getMessage(),
                ]);

                $request->session()->forget('tenant_id');

                if (tenancy()->initialized) {
                    tenancy()->end();
                }

                return redirect()->route('tenant.login')
                    ->withErrors(['property_code' => 'We could not connect to your property. Please log in again or contact NexStay support.']);
            }
        }

        return $next($request);
    }
}
