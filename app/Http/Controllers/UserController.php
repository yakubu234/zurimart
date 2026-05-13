<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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

        return compact('user', 'roles', 'branches', 'permissions');
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
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);

        $role = Role::query()->find($data['role_id']);

        if ($role?->slug === 'production_branch_manager' && empty($data['branch_id'])) {
            validator([], [])->errors()->add('branch_id', 'A production branch manager must be assigned to a branch.');
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_id' => 'A production branch manager must be assigned to a branch.',
            ]);
        }

        return $data;
    }
}
