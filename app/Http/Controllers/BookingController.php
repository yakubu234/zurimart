<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $bookings = Order::query()
            ->with('branch')
            ->when($user?->isBranchRestricted(), fn ($query) => $query->where('branch_id', $user->branch_id))
            ->where(function ($query) {
                $query
                    ->whereDate('scheduled_for', '>', now()->toDateString())
                    ->orWhere(fn ($orQuery) => $orQuery
                        ->where('pricing_tier', 'wholesale')
                        ->whereDate('scheduled_for', '>=', now()->toDateString()));
            })
            ->orderBy('scheduled_for')
            ->get();

        return view('bookings.index', compact('bookings'));
    }
}
