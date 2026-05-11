<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchCapacitySlot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();
        $branches = Branch::query()
            ->with(['capacitySlots' => fn ($query) => $query->whereDate('production_date', $today)])
            ->orderBy('name')
            ->get();

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('branches.form', ['branch' => new Branch()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $branch = Branch::query()->create($data);
        $this->syncTodayCapacity($branch);

        return redirect()->route('branches.index')->with('success', 'Branch added successfully.');
    }

    public function edit(Branch $branch): View
    {
        return view('branches.form', compact('branch'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $data = $this->validatedData($request, $branch->id);
        $branch->update($data);
        $this->syncTodayCapacity($branch);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->orders()->exists()) {
            return back()->withErrors(['branch' => 'This branch already has related orders and cannot be deleted.']);
        }

        $branch->capacitySlots()->delete();
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }

    protected function validatedData(Request $request, ?int $branchId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('branches', 'code')->ignore($branchId)],
            'name' => ['required', 'string', 'max:255'],
            'manager_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'daily_capacity_units' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['available', 'overly_booked'])],
        ]);
    }

    protected function syncTodayCapacity(Branch $branch): void
    {
        BranchCapacitySlot::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'production_date' => now()->toDateString()],
            ['capacity_units' => $branch->daily_capacity_units]
        );
    }
}
