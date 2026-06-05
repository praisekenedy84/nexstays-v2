<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        // Recover an existing session without a subdomain: if the session already
        // has a tenant_id and the user is still authenticated, go straight to the dashboard.
        $tenantId = session('tenant_id');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);

            if ($tenant) {
                tenancy()->initialize($tenant);

                if (Auth::check()) {
                    return redirect()->route('tenant.dashboard');
                }
            }
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $tenant = Tenant::find($request->validated('property_code'));

        if (! $tenant) {
            return back()
                ->withInput($request->only('username', 'property_code'))
                ->withErrors(['property_code' => 'Property code not found. Please check with your manager.']);
        }

        tenancy()->initialize($tenant);

        $user = User::findByLoginIdentifier($request->validated('username'));

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            return back()
                ->withInput($request->only('username', 'property_code'))
                ->withErrors(['username' => 'The provided credentials are incorrect.']);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);

        return redirect()->intended(route('tenant.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}
