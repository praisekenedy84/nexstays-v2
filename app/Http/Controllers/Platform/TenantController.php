<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Room;
use App\Domain\Shared\Actions\UpdateTenantFeatures;
use Spatie\Permission\Models\Role as TenantRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SuspendTenantRequest;
use App\Http\Requests\Platform\UpdateTenantFeaturesRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantFeatures;
use App\Support\Username;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['id', 'created_at']) ? $request->query('sort') : 'created_at';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $tenants = Tenant::query()
            ->when($search, fn ($q) => $q->where('id', 'ilike', "%{$search}%")->orWhereRaw("data->>'name' ilike ?", ["%{$search}%"]))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('platform.tenants.index', compact('tenants', 'sort', 'dir', 'search'));
    }

    public function create(): View
    {
        return view('platform.tenants.create', [
            'featureDefinitions' => TenantFeatures::definitions(),
            'enabledFeatures' => TenantFeatures::keys(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'property_code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:tenants,id'],
            'property_name' => ['required', 'string', 'max:200'],
            'admin_name'     => ['required', 'string', 'max:200'],
            'admin_username' => ['required', 'string', 'max:50', 'regex:'. Username::PATTERN],
            'admin_email'    => ['nullable', 'email', 'max:200'],
            'admin_password' => ['nullable', 'string', 'min:8', 'max:200'],
            'features'       => ['nullable', 'array'],
            'features.*'     => ['string', 'in:'.implode(',', TenantFeatures::keys())],
        ]);

        $password = $validated['admin_password'] ?: Str::password(16);
        $features = array_values(array_intersect(
            TenantFeatures::keys(),
            array_map('strval', $validated['features'] ?? TenantFeatures::keys())
        ));

        // Creating the tenant fires TenantCreated → CreateDatabase + MigrateDatabase (synchronous).
        $tenant = Tenant::create([
            'id'   => $validated['property_code'],
            'name' => $validated['property_name'],
            'enabled_features' => $features,
        ]);

        tenancy()->initialize($tenant);

        app(RoleAndPermissionSeeder::class)->run();

        $admin = User::create([
            'name'              => $validated['admin_name'],
            'username'          => $validated['admin_username'],
            'email'             => $validated['admin_email'] ?? null,
            'password'          => Hash::make($password),
            'email_verified_at' => $validated['admin_email'] ? now() : null,
        ]);
        $admin->syncRoles(['general_manager', 'super_admin']);

        tenancy()->end();

        return redirect()->route('platform.tenants.index')->with('provisioned', [
            'property_code' => $tenant->id,
            'property_name' => $tenant->name,
            'admin_username' => $validated['admin_username'],
            'admin_email'   => $validated['admin_email'] ?? null,
            'password'      => $password,
            'login_url'     => rtrim(config('app.url'), '/').'/login',
        ]);
    }

    public function show(string $tenantId): View
    {
        $tenant = Tenant::findOrFail($tenantId);

        tenancy()->initialize($tenant);

        $users = User::with('roles')->orderBy('name')->get();
        $roles = TenantRole::where('guard_name', 'web')->orderBy('name')->pluck('name');
        $stats = [
            'users'        => User::count(),
            'rooms'        => Room::count(),
            'reservations' => Reservation::count(),
        ];

        tenancy()->end();

        return view('platform.tenants.show', [
            'tenant' => $tenant,
            'users' => $users,
            'roles' => $roles,
            'stats' => $stats,
            'featureDefinitions' => TenantFeatures::definitions(),
            'enabledFeatures' => TenantFeatures::enabled($tenant),
        ]);
    }

    public function updateFeatures(UpdateTenantFeaturesRequest $request, string $tenantId, UpdateTenantFeatures $updateTenantFeatures): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);
        $updateTenantFeatures->execute($tenant, $request->validated('features', []));

        return back()->with('success', "Modules updated for [{$tenantId}].");
    }

    public function runMigrations(string $tenantId): RedirectResponse
    {
        Tenant::findOrFail($tenantId);
        Artisan::call('tenants:migrate', ['--tenants' => [$tenantId], '--force' => true]);

        return back()->with('success', "Migrations applied to [{$tenantId}].");
    }

    public function reseedRoles(string $tenantId): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        try {
            tenancy()->initialize($tenant);
            app(RoleAndPermissionSeeder::class)->run();
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                "Reseed failed for [{$tenantId}]: ".$e->getMessage()
                .' — Try “Run migrations” first, then reseed again.'
            );
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        return back()->with('success', "Roles & permissions reseeded for [{$tenantId}].");
    }

    public function changeUserRole(Request $request, string $tenantId, string $userId): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $userName = null;

        try {
            tenancy()->initialize($tenant);
            $user = User::findOrFail($userId);
            $user->syncRoles([$validated['role']]);
            $userName = $user->name;
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        return back()->with('success', "Role updated for {$userName}.");
    }

    public function resetUserPassword(string $tenantId, string $userId): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        $payload = [];

        try {
            tenancy()->initialize($tenant);
            $user = User::findOrFail($userId);
            $password = Str::password(12);
            $user->update(['password' => Hash::make($password)]);
            $payload = [
                'user_name' => $user->name,
                'username'  => $user->username,
                'email'     => $user->email,
                'password'  => $password,
            ];
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        return back()->with('password_reset', $payload);
    }

    public function updateSettings(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        $validated = $request->validate([
            'sms_sender_name' => ['nullable', 'string', 'max:11', 'regex:/^[A-Za-z0-9 ]+$/'],
        ], [
            'sms_sender_name.max'   => 'Sender name must be 11 characters or fewer (SMS gateway limit).',
            'sms_sender_name.regex' => 'Sender name may only contain letters, numbers, and spaces.',
        ]);

        $tenant->sms_sender_name = $validated['sms_sender_name'] !== '' ? $validated['sms_sender_name'] : null;
        $tenant->save();

        return back()->with('success', 'Hotel settings saved.');
    }

    public function suspend(SuspendTenantRequest $request, string $tenantId): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->suspended_at = now()->toIso8601String();
        $tenant->suspension_reason = $request->validated('reason');
        $tenant->save();

        return back()->with('success', "Hotel [{$tenant->id}] suspended.");
    }

    public function restore(Request $request, string $tenantId): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->suspended_at = null;
        $tenant->suspension_reason = null;
        $tenant->save();

        return back()->with('success', "Hotel [{$tenant->id}] reactivated.");
    }

    public function destroy(string $tenantId): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->delete();

        return redirect()->route('platform.tenants.index')
            ->with('success', "Hotel [{$tenantId}] and its database have been permanently deleted.");
    }
}
