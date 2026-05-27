<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ImpersonateController extends Controller
{
    public function start(Request $request, string $tenantId, string $userId): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        tenancy()->initialize($tenant);
        $user = User::findOrFail($userId);

        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('impersonating', [
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name ?? $tenant->id,
            'user_id'        => $userId,
            'user_name'      => $user->name,
            'user_email'     => $user->email,
            'platform_admin' => Auth::guard('platform_admin')->user()->name,
            'return_url'     => route('platform.tenants.show', $tenantId),
        ]);

        Auth::guard('web')->login($user);

        tenancy()->end();

        return redirect()->route('tenant.dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $returnUrl = session('impersonating.return_url', route('platform.tenants.index'));

        // Do NOT call auth('web')->logout() — it would query the tenant's users table
        // on the central connection (tenancy is not initialized on this route).
        // Clearing the session keys directly is equivalent and requires no DB access.
        $request->session()->forget([
            Auth::guard('web')->getName(),
            'tenant_id',
            'impersonating',
        ]);

        // Also drop the remember-me cookie if one was set during impersonation.
        Cookie::queue(Cookie::forget(Auth::guard('web')->getRecallerName()));

        return redirect($returnUrl);
    }
}
