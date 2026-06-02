<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\BranchInventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private readonly BranchInventoryService $inventory)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user?->canManageAllInventory() || ! is_null($user?->branch_id), 403, 'Assign this user to a branch or grant Manage All Inventory access.');

        $branches = Branch::query()
            ->when($user?->isInventoryRestricted(), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->get();

        $selectedBranchId = $user?->isInventoryRestricted()
            ? $user->branch_id
            : (int) ($request->integer('branch_id') ?: $branches->first()?->id);

        abort_unless($selectedBranchId && $user?->canAccessInventoryBranch($selectedBranchId), 403);

        $selectedBranch = $branches->firstWhere('id', $selectedBranchId) ?? Branch::query()->findOrFail($selectedBranchId);
        $inventoryDate = $request->input('inventory_date', now()->toDateString());
        $rows = $this->inventory->rowsForDate($selectedBranch, $inventoryDate);

        return view('inventory.index', compact('branches', 'selectedBranch', 'inventoryDate', 'rows'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'inventory_date' => ['required', 'date'],
            'rows' => ['required', 'array'],
            'rows.*.opening_units' => ['nullable', 'integer', 'min:0'],
            'rows.*.produced_units' => ['nullable', 'integer', 'min:0'],
            'rows.*.sold_units' => ['nullable', 'integer', 'min:0'],
            'rows.*.adjustment_units' => ['nullable', 'integer', 'min:-1000000'],
        ]);

        abort_unless($request->user()?->canAccessInventoryBranch((int) $data['branch_id']), 403);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        $this->inventory->syncDailyInventory($branch, $data['inventory_date'], $data['rows']);

        return redirect()
            ->route('inventory.index', ['branch_id' => $branch->id, 'inventory_date' => $data['inventory_date']])
            ->with('success', 'Branch inventory saved successfully.');
    }
}
