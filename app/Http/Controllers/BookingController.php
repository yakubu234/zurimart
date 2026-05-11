<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;

class BookingController extends Controller
{
    public function index(): View
    {
        $bookings = Order::query()
            ->with('branch')
            ->whereDate('scheduled_for', '>', now()->toDateString())
            ->orWhere(fn ($query) => $query->where('pricing_tier', 'wholesale')->whereDate('scheduled_for', '>=', now()->toDateString()))
            ->orderBy('scheduled_for')
            ->get();

        return view('bookings.index', compact('bookings'));
    }
}
