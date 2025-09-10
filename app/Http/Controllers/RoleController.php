<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','role:Admin']);
    }

    public function index(Request $request)
    {
        $roles = Role::query()->withCount('users')->orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
        ]);
        Role::create(['name' => $validated['name'], 'guard_name' => config('auth.defaults.guard', 'web')]);
        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $assigned = $role->permissions->pluck('id')->toArray();
        return view('roles.edit', compact('role','permissions','assigned'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);
        $ids = collect($validated['permissions'] ?? [])->map(fn($id)=>(int)$id)->all();
        $perms = Permission::whereIn('id', $ids)->pluck('name')->toArray();
        $role->syncPermissions($perms);
        return redirect()->route('roles.index')->with('success', 'Permissions updated.');
    }

    /**
     * Permissions Matrix: roles as columns, permissions as rows.
     * Admin-only via controller middleware.
     */
    public function matrix(Request $request)
    {
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        // Build assigned lookups: [role_id][permission_id] => true
        $assigned = [];
        foreach ($roles as $role) {
            $permIds = $role->permissions->pluck('id')->all();
            foreach ($permIds as $pid) {
                $assigned[$role->id][$pid] = true;
            }
        }

        return view('roles.matrix', compact('roles','permissions','assigned'));
    }

    /**
     * Sync permissions from matrix form.
     */
    public function syncMatrix(Request $request)
    {
        $payload = $request->validate([
            'assign' => 'array',
        ]);

        $assign = $payload['assign'] ?? [];
        // $assign shape: [role_id => [permission_id => '1', ...], ...]
        $roleIds = array_keys($assign);
        $roles = Role::whereIn('id', $roleIds)->get();

        foreach ($roles as $role) {
            $permIds = array_keys($assign[$role->id] ?? []);
            $permNames = Permission::whereIn('id', $permIds)->pluck('name')->toArray();
            $role->syncPermissions($permNames);
        }

        return redirect()->route('roles.matrix')->with('success', 'Permissions matrix updated.');
    }
}
