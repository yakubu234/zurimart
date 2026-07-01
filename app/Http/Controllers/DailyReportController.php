<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\BranchInventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DailyReportController extends Controller
{
    public function __construct(private readonly BranchInventoryService $dailyReports)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user?->canManageAllDailyReports() || ! is_null($user?->branch_id), 403, 'Assign this user to a branch or grant Manage All Daily Reports access.');

        $branches = Branch::query()
            ->when($user?->isDailyReportRestricted(), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->get();

        $selectedBranchId = $user?->isDailyReportRestricted()
            ? $user->branch_id
            : (int) ($request->integer('branch_id') ?: $branches->first()?->id);

        abort_unless($selectedBranchId && $user?->canAccessDailyReportBranch($selectedBranchId), 403);

        $selectedBranch = $branches->firstWhere('id', $selectedBranchId) ?? Branch::query()->findOrFail($selectedBranchId);
        $reportDate = $request->input('report_date', now()->toDateString());
        $rows = $this->dailyReports->rowsForDate($selectedBranch, $reportDate);

        return view('daily-reports.index', compact('branches', 'selectedBranch', 'reportDate', 'rows'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'report_date' => ['required', 'date'],
            'rows' => ['required', 'array'],
            'rows.*.opening_units' => ['nullable', 'integer', 'min:0'],
            'rows.*.produced_units' => ['nullable', 'integer', 'min:0'],
            'rows.*.sold_units' => ['nullable', 'integer', 'min:0'],
            'rows.*.adjustment_units' => ['nullable', 'integer', 'min:-1000000'],
        ]);

        abort_unless($request->user()?->canAccessDailyReportBranch((int) $data['branch_id']), 403);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        $this->dailyReports->syncDailyInventory($branch, $data['report_date'], $data['rows']);

        return redirect()
            ->route('daily-reports.index', ['branch_id' => $branch->id, 'report_date' => $data['report_date']])
            ->with('success', 'Daily report saved successfully.');
    }
}
