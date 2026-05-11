<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow)
    {
    }

    public function index(): View
    {
        $orders = Order::query()->with(['branch', 'items'])->latest()->paginate(12);

        return view('orders.index', compact('orders'));
    }

    public function create(): View
    {
        $products = Product::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $branches = Branch::query()->where('status', 'available')->orderBy('name')->get();

        return view('orders.create', compact('products', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:100'],
            'customer_type' => ['required', Rule::in(['public_retailer', 'internal_outlet', 'whole_marketer'])],
            'demand_type' => ['required', Rule::in(['retail', 'wholesale'])],
            'scheduled_for' => ['required', 'date', 'after_or_equal:today'],
            'branch_id' => ['required', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $order = $this->workflow->createOrder($data);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', "Order {$order->order_number} created and tagged to {$order->branch->name}.");
    }

    public function show(Order $order): View
    {
        $order->load(['branch', 'items']);

        return view('orders.show', compact('order'));
    }

    public function accept(Order $order): RedirectResponse
    {
        try {
            $this->workflow->acceptOrder($order);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', "Order {$order->order_number} accepted and capacity locked.");
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->workflow->rejectOrder($order, $data['rejection_reason'] ?? null);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', "Order {$order->order_number} rejected and ready for re-routing.");
    }
}
