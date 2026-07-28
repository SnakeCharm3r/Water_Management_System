<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        $grouped = $permissions->groupBy(fn ($p) => explode('.', $p->name)[0]);

        return view('roles.create', compact('permissions', 'grouped'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100|unique:roles,name',
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created with " . count($validated['permissions']) . " permissions.");
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('name')->get();
        $grouped = $permissions->groupBy(fn ($p) => explode('.', $p->name)[0]);

        return view('roles.edit', compact('role', 'permissions', 'grouped'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100|unique:roles,name,' . $role->id,
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' updated.");
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', "Cannot delete role '{$role->name}' — it is assigned to {$role->users()->count()} user(s).");
        }

        $role->syncPermissions([]);
        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' deleted.");
    }
}
