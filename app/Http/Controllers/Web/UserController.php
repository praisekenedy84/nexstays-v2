<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreUserRequest;
use App\Http\Requests\Web\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(25);

        return view('hbms.users.index', compact('users'));
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
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);
        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('tenant.users.index')->with('success', 'Staff member created.');
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
