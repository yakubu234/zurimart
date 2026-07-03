<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\RawMaterial;
use App\Models\RawMaterialMovement;
use App\Services\RawMaterialInventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(private readonly RawMaterialInventoryService $inventory)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user?->isAdministrator() || ! is_null($user?->branch_id), 403, 'Assign this user to a branch or use an Admin or Super Admin account.');

        $branches = Branch::query()
            ->when($user?->isInventoryRestricted(), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->get();

        $selectedBranchId = $user?->isInventoryRestricted()
            ? $user->branch_id
            : (int) ($request->integer('branch_id') ?: $branches->first()?->id);

        abort_unless($selectedBranchId && $user?->canAccessInventoryBranch($selectedBranchId), 403);

        $selectedBranch = $branches->firstWhere('id', $selectedBranchId)
            ?? Branch::query()->findOrFail($selectedBranchId);
        $pageSizes = [10, 25, 50, 100];
        $stockPerPage = in_array($request->integer('stock_per_page'), $pageSizes, true)
            ? $request->integer('stock_per_page')
            : 10;
        $activityPerPage = in_array($request->integer('activity_per_page'), $pageSizes, true)
            ? $request->integer('activity_per_page')
            : 10;

        $allStockRows = $this->inventory->stockRows($selectedBranch);
        $stockPage = max(1, $request->integer('stock_page', 1));
        $stockRows = new LengthAwarePaginator(
            $allStockRows->forPage($stockPage, $stockPerPage)->values(),
            $allStockRows->count(),
            $stockPerPage,
            $stockPage,
            [
                'path' => $request->url(),
                'pageName' => 'stock_page',
            ]
        );
        $stockRows->appends($request->except('stock_page'));

        $recentMovements = $this->inventory
            ->recentMovements($selectedBranch, $activityPerPage)
            ->appends($request->except('activity_page'));
        $movementMaterials = RawMaterial::query()->orderBy('name')->get();

        return view('inventory.index', compact(
            'branches',
            'selectedBranch',
            'allStockRows',
            'stockRows',
            'recentMovements',
            'movementMaterials',
            'pageSizes',
            'stockPerPage',
            'activityPerPage'
        ));
    }

    public function materials(Request $request): View
    {
        abort_unless($request->user()?->canManageAllInventory(), 403);

        $materials = RawMaterial::query()->orderBy('name')->get();

        return view('inventory.materials', compact('materials'));
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'raw_material_id' => [
                'required',
                Rule::exists('raw_materials', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'movement_type' => ['required', Rule::in(['received', 'used'])],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'movement_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($request->user()?->canAccessInventoryBranch((int) $data['branch_id']), 403);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        $material = RawMaterial::query()->findOrFail($data['raw_material_id']);
        $this->inventory->recordMovement($branch, $material, $request->user(), $data);

        $action = $data['movement_type'] === 'received' ? 'received into' : 'used by';

        return redirect()
            ->route('inventory.index', ['branch_id' => $branch->id])
            ->with('success', "{$material->name} was recorded as {$action} {$branch->name}.");
    }

    public function updateMovement(Request $request, RawMaterialMovement $rawMaterialMovement): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('edit-inventory-movements'), 403);
        abort_unless($request->user()?->canAccessInventoryBranch($rawMaterialMovement->branch_id), 403);

        $data = $request->validate([
            'raw_material_id' => ['required', 'exists:raw_materials,id'],
            'movement_type' => ['required', Rule::in(['received', 'used'])],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'movement_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $movement = $this->inventory->updateMovement($rawMaterialMovement, $data);

        return back()->with('success', "{$movement->rawMaterial->name} inventory activity was updated successfully.");
    }

    public function destroyMovement(Request $request, RawMaterialMovement $rawMaterialMovement): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('delete-inventory-movements'), 403);
        abort_unless($request->user()?->canAccessInventoryBranch($rawMaterialMovement->branch_id), 403);

        $materialName = $rawMaterialMovement->rawMaterial?->name ?? 'Raw material';
        $this->inventory->deleteMovement($rawMaterialMovement);

        return back()->with('success', "{$materialName} inventory activity was deleted successfully.");
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageAllInventory(), 403);

        RawMaterial::query()->create($this->validatedMaterial($request));

        return back()->with('success', 'Raw material added successfully.');
    }

    public function updateMaterial(Request $request, RawMaterial $rawMaterial): RedirectResponse
    {
        abort_unless($request->user()?->canManageAllInventory(), 403);

        $rawMaterial->update($this->validatedMaterial($request, $rawMaterial));

        return back()->with('success', 'Raw material updated successfully.');
    }

    private function validatedMaterial(Request $request, ?RawMaterial $rawMaterial = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('raw_materials', 'code')->ignore($rawMaterial?->id)],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
