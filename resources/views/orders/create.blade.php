@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', $order->exists ? 'Edit Order' : 'Create Order')
@section('page_title', $order->exists ? 'Edit Bakery Order' : 'Create and Tag a Bakery Order')
@section('page_intro', $order->exists ? 'Update customer details, branch assignment, production date, and item quantities before the order is completed.' : 'Capture customer type, demand type, scheduled production date, and product quantities. Wholesale pricing is triggered automatically from 50 units upward.')

@section('page')
    @php
        $existingItems = $order->exists ? $order->items->pluck('quantity', 'product_id') : collect();
    @endphp

    <style>
        .order-form-page .card-title {
            font-size: 1.2rem;
        }

        .order-form-page .card-body {
            padding: 1.25rem;
        }

        .order-form-page .form-control,
        .order-form-page textarea,
        .order-form-page select,
        .order-form-page .btn {
            min-height: 46px;
        }

        .order-form-page .product-table td,
        .order-form-page .product-table th {
            vertical-align: middle;
        }

        .order-form-page .product-table td:last-child {
            min-width: 110px;
        }

        .order-form-page .quantity-input {
            max-width: 100%;
            text-align: center;
            font-size: 16px;
        }

        .order-form-page .order-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        @media (max-width: 991.98px) {
            .order-form-page .order-form-sidebar {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 767.98px) {
            .order-form-page .card-body {
                padding: 1rem;
            }

            .order-form-page .card-header,
            .order-form-page .card-footer {
                padding: 0.9rem 1rem;
            }

            .order-form-page .product-table thead {
                display: none;
            }

            .order-form-page .product-table,
            .order-form-page .product-table tbody,
            .order-form-page .product-table tr,
            .order-form-page .product-table td {
                display: block;
                width: 100%;
            }

            .order-form-page .product-table tr {
                border-top: 1px solid #dee2e6;
                padding: 0.85rem 0;
            }

            .order-form-page .product-table tbody tr:first-child {
                border-top: 0;
            }

            .order-form-page .product-table td {
                border: 0;
                padding: 0.35rem 0;
                text-align: left;
            }

            .order-form-page .product-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #6c757d;
                margin-bottom: 0.2rem;
            }

            .order-form-page .product-table td[data-label="Product"]::before {
                margin-bottom: 0.35rem;
            }

            .order-form-page .product-table td[data-label="Qty"] {
                padding-top: 0.55rem;
            }

            .order-form-page .product-table td[data-label="Qty"] .quantity-input {
                max-width: none;
                width: 100%;
            }

            .order-form-page .order-actions {
                flex-direction: column;
            }

            .order-form-page .order-actions .btn {
                width: 100%;
            }
        }
    </style>

    <form action="{{ $order->exists ? route('orders.update', $order) : route('orders.store') }}" method="POST" class="order-form-page">
        @csrf
        @if ($order->exists)
            @method('PUT')
        @endif
        <div class="row">
            <div class="col-lg-5 order-form-sidebar">
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
                        <table class="table table-striped mb-0 product-table">
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
                                        <td data-label="Product">
                                            <strong>{{ $product->name }}</strong><br>
                                            <small class="text-muted">{{ $product->weight_grams }}g</small>
                                        </td>
                                        <td data-label="Category">{{ $product->category }}</td>
                                        <td data-label="Retail">N{{ number_format($product->retail_price, 0) }}</td>
                                        <td data-label="Wholesale">N{{ number_format($product->wholesale_price, 0) }}</td>
                                        <td data-label="Stock">{{ $product->stock_units }}</td>
                                        <td data-label="Qty">
                                            <input type="number" min="0" inputmode="numeric" name="items[{{ $product->id }}]" value="{{ old('items.' . $product->id, $existingItems[$product->id] ?? 0) }}" class="form-control quantity-input">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="order-actions">
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
        </div>
    </form>
@endsection
