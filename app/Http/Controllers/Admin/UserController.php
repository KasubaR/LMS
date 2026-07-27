<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->string('role')->isNotEmpty(), fn ($query) => $query->whereHas(
                'roles',
                fn ($roleQuery) => $roleQuery->where('name', $request->string('role'))
            ))
            ->when($request->has('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $password = Str::password(16);

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($password),
        ]);

        $user->forceFill(['is_active' => true, 'must_change_password' => true])->save();

        $user->assignRole($request->validated('role'));

        AuditLogger::log('User Created', $user, [], 'Users');

        return redirect()
            ->route('admin.users.index')
            ->with('generated_password', $password)
            ->with('status', "User \"{$user->name}\" created.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('admin.users.index')->with('status', "User \"{$user->name}\" updated.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot deactivate your own account.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.users.index')->with('status', "User \"{$user->name}\" {$status}.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $password = Str::password(16);

        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ])->save();

        AuditLogger::log('Password Changed', $user, [], 'Users');

        return redirect()
            ->route('admin.users.index')
            ->with('generated_password', $password)
            ->with('status', "Password reset for \"{$user->name}\".");
    }
}
