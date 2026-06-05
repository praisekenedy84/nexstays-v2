<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\RoleController;
use App\Http\Requests\Web\StoreUserRequest;
use App\Http\Requests\Web\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['name', 'username', 'email', 'created_at']) ? $request->query('sort') : 'name';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->with('roles')
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('username', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")))
            ->orderBy($sort, $dir)
            ->paginate(25)
            ->withQueryString();

        return view('hbms.users.index', compact('users', 'sort', 'dir', 'search'));
    }

    public function create(): View
    {
        return view('hbms.users.form', [
            'user' => new User,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);
        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('tenant.users.index')->with('success', 'Staff member created.');
    }

    public function show(User $user): View
    {
        $user->loadMissing('roles');

        return view('hbms.users.show', [
            'user'             => $user,
            'userRole'         => $user->roles->first(),
            'userPermissions'  => $user->getAllPermissions()->pluck('name')->flip()->all(),
            'permissionGroups' => RoleController::PERMISSION_GROUPS,
        ]);
    }

    public function edit(User $user): View
    {
        return view('hbms.users.form', [
            'user' => $user,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->validated('name'),
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
        ];
        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }
        $user->update($data);
        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('tenant.users.index')->with('success', 'Staff member updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-users'), 403);
        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');

        $user->delete();

        return redirect()->route('tenant.users.index')->with('success', 'Staff member removed.');
    }
}
