<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Services\TenantResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly TenantResolver $tenantResolver)
    {
    }

    public function create(): View|RedirectResponse|Response
    {
        // Recover an existing session without a subdomain: if the session already
        // has a tenant_id and the user is still authenticated, go straight to the dashboard.
        $tenantId = session('tenant_id');

        if ($tenantId) {
            $tenant = $this->tenantResolver->find($tenantId);

            if ($tenant && $this->tenantResolver->isSuspended($tenant)) {
                return $this->denySuspendedTenant($tenant->id, $tenant->suspension_reason);
            }

            if ($tenant) {
                try {
                    tenancy()->initialize($tenant);
                } catch (Throwable $e) {
                    Log::error('Tenancy initialization failed while loading the login page.', [
                        'tenant_id' => $tenant->id,
                        'exception' => $e->getMessage(),
                    ]);

                    if (tenancy()->initialized) {
                        tenancy()->end();
                    }

                    session()->forget('tenant_id');

                    return view('auth.login');
                }

                if (Auth::check()) {
                    return redirect()->route('tenant.dashboard');
                }
            }
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse|Response
    {
        $tenant = Tenant::find($request->validated('property_code'));

        if (! $tenant) {
            return back()
                ->withInput($request->only('username', 'property_code'))
                ->withErrors(['property_code' => 'Property code not found. Please check with your manager.']);
        }

        if ($this->tenantResolver->isSuspended($tenant)) {
            return $this->denySuspendedTenant($tenant->id, $tenant->suspension_reason);
        }

        try {
            tenancy()->initialize($tenant);
        } catch (Throwable $e) {
            Log::error('Tenancy initialization failed during login.', [
                'tenant_id' => $tenant->id,
                'exception' => $e->getMessage(),
            ]);

            if (tenancy()->initialized) {
                tenancy()->end();
            }

            return back()
                ->withInput($request->only('username', 'property_code'))
                ->withErrors(['property_code' => 'We could not connect to your property. Please try again or contact NexStay support.']);
        }

        $user = User::findByLoginIdentifier($request->validated('username'));

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            return back()
                ->withInput($request->only('username', 'property_code'))
                ->withErrors(['username' => 'The provided credentials are incorrect.']);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);

        // Marks that this browser had an active login, so an idle session
        // timeout can be distinguished from "never logged in" and reported
        // to the user with a clearer message.
        Cookie::queue(Cookie::make('nexstay_active', '1', (int) config('session.lifetime')));

        return redirect()->intended(route('tenant.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Cookie::queue(Cookie::forget('nexstay_active'));

        return redirect()->route('tenant.login');
    }

    private function denySuspendedTenant(string $propertyCode, ?string $reason = null): Response
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        Auth::guard('web')->forgetUser();
        session()->invalidate();
        session()->regenerateToken();

        Cookie::queue(Cookie::forget('nexstay_active'));

        return response()->view('errors.tenant-suspended', [
            'propertyCode' => $propertyCode,
            'reason' => $reason,
        ], 503);
    }
}
