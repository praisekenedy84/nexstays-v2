<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyBySession
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->session()->get('tenant_id');

        if (! $tenantId) {
            return redirect()->route('tenant.login');
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $request->session()->forget('tenant_id');

            return redirect()->route('tenant.login')
                ->withErrors(['property_code' => 'Your property could not be found. Please log in again.']);
        }

        if (! empty($tenant->suspended_at)) {
            $request->session()->forget('tenant_id');

            return redirect()->route('tenant.login')
                ->withErrors(['property_code' => 'This property has been suspended. Please contact NexStay support.']);
        }

        if (! tenancy()->initialized) {
            tenancy()->initialize($tenant);
        }

        return $next($request);
    }
}
