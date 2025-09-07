<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with their permissions.
     */
    public function index(): Response
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return Inertia::render('settings/Roles', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name')]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }

        return back()->with('message', 'Role created successfully.');
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update(['name' => $request->validated('name')]);

        $role->syncPermissions($request->validated('permissions', []));

        return back()->with('message', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Prevent deletion of admin role
        if ($role->name === 'admin') {
            return back()->withErrors(['role' => 'Cannot delete the admin role.']);
        }

        $role->delete();

        return back()->with('message', 'Role deleted successfully.');
    }
}
