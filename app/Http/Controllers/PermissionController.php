<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Services\AuditTrailService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function __construct(private readonly AuditTrailService $auditTrail)
    {
    }

    public function index(): View
    {
        $permissions = Permission::query()->withCount(['roles', 'users'])->orderBy('group')->orderBy('name')->get();

        return view('permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        return view('permissions.form', ['permission' => new Permission()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        Permission::query()->create([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'group' => $data['group'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission): View
    {
        return view('permissions.form', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $this->validatedData($request, $permission);

        $permission->update([
            'slug' => $permission->is_system ? $permission->slug : $data['slug'],
            'name' => $data['name'],
            'group' => $data['group'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($permission->is_system) {
            return back()->withErrors(['permission' => 'System permissions cannot be deleted.']);
        }

        $roleSlugs = $permission->roles()->orderBy('slug')->pluck('slug')->all();
        $userEmails = $permission->users()->orderBy('email')->pluck('email')->all();
        $this->auditTrail->recordChange(
            $permission,
            "Removed assignments before deleting permission: {$permission->name}",
            ['roles' => $roleSlugs, 'users' => $userEmails],
            ['roles' => [], 'users' => []]
        );
        $permission->roles()->detach();
        $permission->users()->detach();
        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully.');
    }

    protected function validatedData(Request $request, ?Permission $permission = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('permissions', 'slug')->ignore($permission?->id)],
            'group' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['name'], '_');
        $data['group'] = Str::slug($data['group'], '_');

        return $data;
    }
}
