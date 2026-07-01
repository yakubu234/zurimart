<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditTrailService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct(private readonly AuditTrailService $auditTrail)
    {
    }

    public function index(): View
    {
        $roles = Role::query()->withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.form', $this->formData(new Role()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $role = Role::query()->create([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        $role->permissions()->sync($data['permission_ids'] ?? []);
        $permissions = $role->permissions()->orderBy('slug')->pluck('slug')->all();
        $this->auditTrail->recordChange(
            $role,
            "Assigned permissions to role: {$role->name}",
            ['permissions' => []],
            ['permissions' => $permissions]
        );

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('roles.form', $this->formData($role));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validatedData($request, $role);
        $oldPermissions = $role->permissions()->orderBy('slug')->pluck('slug')->all();

        $role->update([
            'slug' => $role->is_system ? $role->slug : $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permission_ids'] ?? []);
        $newPermissions = $role->permissions()->orderBy('slug')->pluck('slug')->all();
        $this->auditTrail->recordChange(
            $role,
            "Changed permissions for role: {$role->name}",
            ['permissions' => $oldPermissions],
            ['permissions' => $newPermissions]
        );

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'System roles cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'This role is currently assigned to one or more users.']);
        }

        $oldPermissions = $role->permissions()->orderBy('slug')->pluck('slug')->all();
        $this->auditTrail->recordChange(
            $role,
            "Removed permissions before deleting role: {$role->name}",
            ['permissions' => $oldPermissions],
            ['permissions' => []]
        );
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    protected function formData(Role $role): array
    {
        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group');

        return compact('role', 'permissions');
    }

    protected function validatedData(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('roles', 'slug')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['name'], '_');

        return $data;
    }
}
