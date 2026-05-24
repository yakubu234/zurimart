@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', $order->exists ? 'Edit Order' : 'Create Order')
@section('page_title', $order->exists ? 'Edit Bakery Order' : 'Create and Tag a Bakery Order')
@section('page_intro', $order->exists ? 'Update customer details, branch assignment, production date, and item quantities before the order is completed.' : 'Capture customer type, demand type, scheduled production date, and product quantities. Wholesale pricing is triggered automatically from 50 units upward.')

@section('page')
    @php
        $existingItems = $order->exists ? $order->items->pluck('quantity', 'product_id') : collect();
    @endphp

    <form action="{{ $order->exists ? route('orders.update', $order) : route('orders.store') }}" method="POST">
        @csrf
        @if ($order->exists)
            @method('PUT')
        @endif
        <div class="row">
            <div class="col-lg-5">
                <div class="card card-warning card-outline">
                    <div class="card-header"><h3 class="card-title">Customer and Routing Details</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $order->customer_name) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $order->customer_email) }}">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $order->customer_phone) }}">
                        </div>
                        <div class="form-group">
                            <label>Access Level</label>
                            <select name="customer_type" class="form-control">
                                <option value="public_retailer" @selected(old('customer_type', $order->customer_type) === 'public_retailer')>Public Retailer</option>
                                <option value="internal_outlet" @selected(old('customer_type', $order->customer_type) === 'internal_outlet')>Internal Outlet / Minimart</option>
                                <option value="whole_marketer" @selected(old('customer_type', $order->customer_type) === 'whole_marketer')>Whole Marketer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Demand Type</label>
                            <select name="demand_type" class="form-control">
                                <option value="retail" @selected(old('demand_type', $order->demand_type) === 'retail')>Retail Demand</option>
                                <option value="wholesale" @selected(old('demand_type', $order->demand_type) === 'wholesale')>Wholesale Demand</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Production Date</label>
                            <input type="date" name="scheduled_for" class="form-control" value="{{ old('scheduled_for', $order->scheduled_for?->toDateString() ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Tagged Branch</label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">Select available branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $order->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Only branches marked available are listed here. If the total units exceed the remaining daily capacity, the order will be stopped before submission succeeds.</small>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="4" class="form-control">{{ old('notes', $order->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Product Quantities</h3></div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Retail</th>
                                    <th>Wholesale</th>
                                    <th>Stock</th>
                                    <th style="width: 120px;">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td>
                                            <strong>{{ $product->name }}</strong><br>
                                            <small class="text-muted">{{ $product->weight_grams }}g</small>
                                        </td>
                                        <td>{{ $product->category }}</td>
                                        <td>N{{ number_format($product->retail_price, 0) }}</td>
                                        <td>N{{ number_format($product->wholesale_price, 0) }}</td>
                                        <td>{{ $product->stock_units }}</td>
                                        <td>
                                            <input type="number" min="0" name="items[{{ $product->id }}]" value="{{ old('items.' . $product->id, $existingItems[$product->id] ?? 0) }}" class="form-control">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning">{{ $order->exists ? 'Save Order Changes' : 'Submit for Branch Approval' }}</button>
                        @auth
                            <a href="{{ $order->exists ? route('orders.show', $order) : route('orders.index') }}" class="btn btn-default">Cancel</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-default">Admin Login</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
