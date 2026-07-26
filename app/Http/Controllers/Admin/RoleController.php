<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        return redirect()->route('admin.roles.index')->with('status', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        return redirect()->route('admin.roles.index')->with('status', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')->with('error', "Role \"{$role->name}\" is still assigned to users and cannot be deleted.");
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', "Role \"{$role->name}\" deleted.");
    }
}
