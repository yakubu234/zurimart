<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\NotificationEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with(['branch', 'roleRecord', 'permissions'])
            ->orderBy('name')
            ->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.form', $this->formData(new User()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $role = Role::query()->findOrFail($data['role_id']);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'role_id' => $role->id,
            'role_code' => $role->slug,
            'role' => in_array($role->slug, User::legacyRoleKeys(), true) ? $role->slug : 'public_retailer',
            'status' => $data['status'],
            'password' => $data['password'],
            'notification_preferences' => $data['notification_preferences'],
        ]);

        $user->permissions()->sync($data['permission_ids'] ?? []);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $user->load(['roleRecord', 'permissions']);

        return view('users.form', $this->formData($user));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validatedData($request, $user);
        $role = Role::query()->findOrFail($data['role_id']);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'role_id' => $role->id,
            'role_code' => $role->slug,
            'role' => in_array($role->slug, User::legacyRoleKeys(), true) ? $role->slug : $user->role,
            'status' => $data['status'],
            'notification_preferences' => $data['notification_preferences'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->permissions()->sync($data['permission_ids'] ?? []);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['user' => 'You cannot delete your own user account.']);
        }

        if ($user->hasRole('super_admin') && User::query()->where('role_code', 'super_admin')->count() <= 1) {
            return back()->withErrors(['user' => 'The last super admin cannot be deleted.']);
        }

        $user->permissions()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    protected function formData(User $user): array
    {
        $roles = Role::query()->orderBy('name')->get();
        $branches = Branch::query()->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group');
        $notificationEvents = NotificationEvents::USER;

        return compact('user', 'roles', 'branches', 'permissions', 'notificationEvents');
    }

    protected function validatedData(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'notification_preferences' => ['nullable', 'array'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]) + [
            'notification_preferences' => NotificationEvents::sanitize(
                NotificationEvents::USER,
                $request->input('notification_preferences', [])
            ),
        ];

        $role = Role::query()->with('permissions')->find($data['role_id']);

        if ($role && $this->roleRequiresBranchAssignment($role) && empty($data['branch_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_id' => 'This admin role must be assigned to a branch unless it has permission to manage all branches.',
            ]);
        }

        return $data;
    }

    protected function roleRequiresBranchAssignment(Role $role): bool
    {
        $permissionSlugs = $role->permissions->pluck('slug')->all();
        $branchScopedPermissions = [
            'manage-branches',
            'manage-branch-master-data',
            'manage-order-approvals',
            'view-bookings',
            'view-reports',
        ];

        return collect($permissionSlugs)->intersect($branchScopedPermissions)->isNotEmpty()
            && ! in_array('manage-all-branches', $permissionSlugs, true);
    }
}
