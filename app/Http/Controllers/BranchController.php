<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchCapacitySlot;
use App\Support\NotificationEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();
        $user = Auth::user();
        $branches = Branch::query()
            ->withCount('orders')
            ->with(['capacitySlots' => fn ($query) => $query->whereDate('production_date', $today)])
            ->when($user?->isBranchRestricted(), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->get();

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        abort_unless(Auth::user()?->canManageAllBranches(), 403);

        return view('branches.form', [
            'branch' => new Branch(),
            'notificationEvents' => NotificationEvents::BRANCH,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageAllBranches(), 403);

        $data = $this->validatedData($request);
        $branch = Branch::query()->create($data);
        $this->syncTodayCapacity($branch);

        return redirect()->route('branches.index')->with('success', 'Branch added successfully.');
    }

    public function edit(Branch $branch): View
    {
        abort_unless(request()->user()?->canAccessBranch($branch->id), 403);

        return view('branches.form', [
            'branch' => $branch,
            'notificationEvents' => NotificationEvents::BRANCH,
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        abort_unless($request->user()?->canAccessBranch($branch->id), 403);

        $data = $this->validatedData($request, $branch->id);
        $branch->update($data);
        $this->syncTodayCapacity($branch);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        abort_unless(request()->user()?->canAccessBranch($branch->id), 403);

        $hasOrders = $branch->orders()->exists();

        if ($hasOrders && ! request()->user()?->hasPermission('delete-branches-with-orders')) {
            return back()->withErrors([
                'branch' => 'This branch already has related orders. Only users with permission to delete branches with order history can remove it.',
            ]);
        }

        $branch->capacitySlots()->delete();
        $branch->delete();

        $message = $hasOrders
            ? 'Branch deleted successfully. Existing orders were preserved and are now unassigned from the deleted branch.'
            : 'Branch deleted successfully.';

        return redirect()->route('branches.index')->with('success', $message);
    }

    protected function validatedData(Request $request, ?int $branchId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('branches', 'code')->ignore($branchId)],
            'name' => ['required', 'string', 'max:255'],
            'manager_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'whatsapp_phone' => ['nullable', 'string', 'max:100'],
            'notification_preferences' => ['nullable', 'array'],
            'address' => ['nullable', 'string', 'max:255'],
            'daily_capacity_units' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['available', 'overly_booked'])],
        ]) + [
            'notification_preferences' => NotificationEvents::sanitize(
                NotificationEvents::BRANCH,
                $request->input('notification_preferences', [])
            ),
        ];
    }

    protected function syncTodayCapacity(Branch $branch): void
    {
        BranchCapacitySlot::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'production_date' => now()->toDateString()],
            ['capacity_units' => $branch->daily_capacity_units]
        );
    }
}
