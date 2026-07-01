<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Services\AppSettingsService;
use App\Services\OrderWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderWorkflowService $workflow,
        private readonly AppSettingsService $settings
    ) {
    }

    public function index(): View
    {
        $user = Auth::user();
        $orders = Order::query()
            ->with(['branch', 'items', 'creator'])
            ->when($user?->isBranchRestricted(), fn ($query) => $query->where('branch_id', $user->branch_id))
            ->latest()
            ->paginate(12);

        return view('orders.index', compact('orders'));
    }

    public function create(): View
    {
        $products = Product::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $user = Auth::user();
        $branches = Branch::query()
            ->where('status', 'available')
            ->when($user?->isBranchRestricted(), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->get();
        $order = new Order();
        $retailMinimumUnits = max(1, (int) $this->settings->get('orders.retail_minimum_units', 1));
        $wholesaleMinimumUnits = max($retailMinimumUnits, (int) $this->settings->get('orders.wholesale_minimum_units', 50));

        return view('orders.create', compact('products', 'branches', 'order', 'retailMinimumUnits', 'wholesaleMinimumUnits'));
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

        $data['created_by'] = $request->user()?->id;

        if ($request->user()?->isBranchRestricted() && ! $request->user()?->canAccessBranch((int) $data['branch_id'])) {
            throw ValidationException::withMessages([
                'branch_id' => 'You can only create orders for your assigned branch.',
            ]);
        }

        try {
            $order = $this->workflow->createOrder($data);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $successMessage = "Order {$order->order_number} created and tagged to {$order->branch->name}.";

        if (! $request->user()) {
            return redirect()
                ->route('orders.create')
                ->with('success', $successMessage . ' Our production team can now review it.');
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', $successMessage);
    }

    public function edit(Order $order): View
    {
        abort_unless(! request()->user()?->isBranchRestricted() || request()->user()?->canAccessBranch($order->branch_id), 403);
        abort_unless(request()->user()?->canEditOrder($order), 403);

        if ($order->status === 'completed') {
            abort(403, 'Completed orders can no longer be edited.');
        }

        $order->load('items');
        $products = Product::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $user = Auth::user();
        $branches = Branch::query()
            ->when(
                $user?->isBranchRestricted(),
                fn ($query) => $query->whereKey($user->branch_id),
                fn ($query) => $query
                    ->where(function ($branchQuery) use ($order) {
                        $branchQuery
                            ->where('status', 'available')
                            ->orWhere('id', $order->branch_id);
                    })
            )
            ->orderBy('name')
            ->get();
        $retailMinimumUnits = max(1, (int) $this->settings->get('orders.retail_minimum_units', 1));
        $wholesaleMinimumUnits = max($retailMinimumUnits, (int) $this->settings->get('orders.wholesale_minimum_units', 50));

        return view('orders.create', compact('products', 'branches', 'order', 'retailMinimumUnits', 'wholesaleMinimumUnits'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        abort_unless(! $request->user()?->isBranchRestricted() || $request->user()?->canAccessBranch($order->branch_id), 403);
        abort_unless($request->user()?->canEditOrder($order), 403);

        if ($order->status === 'completed') {
            return back()->withErrors(['order' => 'Completed orders can no longer be edited.']);
        }

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

        if ($request->user()?->isBranchRestricted() && ! $request->user()?->canAccessBranch((int) $data['branch_id'])) {
            throw ValidationException::withMessages([
                'branch_id' => 'You can only keep this order under your assigned branch.',
            ]);
        }

        try {
            $order = $this->workflow->updateOrder($order, $data);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', "Order {$order->order_number} updated successfully.");
    }

    public function show(Order $order): View
    {
        abort_unless(! request()->user()?->isBranchRestricted() || request()->user()?->canAccessBranch($order->branch_id), 403);

        $order->load(['branch', 'items']);

        return view('orders.show', compact('order'));
    }

    public function destroy(Order $order): RedirectResponse
    {
        abort_unless(! request()->user()?->isBranchRestricted() || request()->user()?->canAccessBranch($order->branch_id), 403);

        $orderNumber = $order->order_number;
        $this->workflow->deleteOrder($order);

        return redirect()
            ->route('orders.index')
            ->with('success', "Order {$orderNumber} deleted successfully.");
    }

    public function accept(Order $order): RedirectResponse
    {
        abort_unless(! request()->user()?->isBranchRestricted() || request()->user()?->canAccessBranch($order->branch_id), 403);

        try {
            $this->workflow->acceptOrder($order);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', "Order {$order->order_number} accepted and capacity locked.");
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        abort_unless(! $request->user()?->isBranchRestricted() || $request->user()?->canAccessBranch($order->branch_id), 403);

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
