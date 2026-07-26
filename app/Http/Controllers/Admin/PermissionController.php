<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index(): View
    {
        return view('admin.permissions.index', [
            'permissions' => Permission::orderBy('name')->get(),
            'roles' => Role::with('permissions')->orderBy('name')->get(),
        ]);
    }
}
