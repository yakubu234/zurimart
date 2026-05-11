<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(): View
    {
        $salesTrend = collect(range(0, 6))
            ->map(function (int $offset) {
                $date = Carbon::now()->subDays(6 - $offset)->toDateString();

                return [
                    'day' => Carbon::parse($date)->format('D'),
                    'retail' => (int) Order::query()->whereDate('created_at', $date)->where('pricing_tier', 'retail')->sum('total_units'),
                    'wholesale' => (int) Order::query()->whereDate('created_at', $date)->where('pricing_tier', 'wholesale')->sum('total_units'),
                ];
            });

        $branchPerformance = Order::query()
            ->select('branches.name', DB::raw('count(orders.id) as orders_count'))
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->where('orders.status', 'accepted')
            ->groupBy('branches.name')
            ->orderByDesc('orders_count')
            ->get();

        return view('reports.index', compact('salesTrend', 'branchPerformance'));
    }
}
