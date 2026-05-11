@extends('layouts.app')

@section('title', 'Order Receipt')
@section('page_title', 'Order Receipt')
@section('page_intro', 'A clearer AdminLTE receipt view with printable order details, branch contact information, and pricing breakdown.')

@section('page')
    <div class="mb-3">
        <button class="btn btn-warning" onclick="window.print()">Print / Save PDF</button>
        <a href="{{ route('orders.index') }}" class="btn btn-default">Back to Orders</a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">ZuriMart Bakery Receipt</h3>
                    <div class="card-tools">
                        @include('partials.badge', ['value' => $order->status])
                        @include('partials.badge', ['value' => $order->pricing_tier])
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4><strong>ZuriMart Bakery</strong></h4>
                            <address class="mb-0">
                                zurimartbakeryservices.com<br>
                                info@zurimartbakeryservices.com<br>
                                orders@zurimartbakeryservices.com
                            </address>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <strong>Order ID:</strong> {{ $order->order_number }}<br>
                            <strong>Branch:</strong> {{ $order->branch?->name ?? 'Unassigned' }}<br>
                            <strong>Date:</strong> {{ $order->scheduled_for->format('d M Y') }}
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-user"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Customer</span>
                                    <span class="info-box-number">{{ $order->customer_name }}</span>
                                    <span>{{ $order->customer_email ?: 'No email provided' }}</span><br>
                                    <span>{{ $order->customer_phone ?: 'No phone provided' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-industry"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tagged Branch</span>
                                    <span class="info-box-number">{{ $order->branch?->name }}</span>
                                    <span>{{ $order->branch?->phone }}</span><br>
                                    <span>{{ $order->branch?->email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-right">Weight</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td class="text-right">{{ number_format($item->unit_weight_grams / 1000, 2) }}kg</td>
                                        <td class="text-right">{{ $item->quantity }}</td>
                                        <td class="text-right">N{{ number_format($item->unit_price, 0) }}</td>
                                        <td class="text-right">N{{ number_format($item->line_total, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title">Order Summary</h3></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Total Units</th><td class="text-right">{{ $order->total_units }}</td></tr>
                        <tr><th>Total Weight</th><td class="text-right">{{ number_format($order->total_weight_grams / 1000, 2) }}kg</td></tr>
                        <tr><th>Subtotal</th><td class="text-right">N{{ number_format($order->subtotal_amount, 0) }}</td></tr>
                        <tr><th>Discount</th><td class="text-right">N{{ number_format($order->discount_amount, 0) }}</td></tr>
                        <tr><th>Total Payable</th><td class="text-right"><strong>N{{ number_format($order->total_amount, 0) }}</strong></td></tr>
                    </table>

                    <div class="mt-3">
                        <strong>Demand Type:</strong> @include('partials.badge', ['value' => $order->demand_type])
                    </div>

                    @if ($order->rejection_reason)
                        <div class="alert alert-danger mt-3 mb-0">
                            <strong>Rejection Note:</strong> {{ $order->rejection_reason }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        @media print {
            .main-sidebar, .main-header, .content-header, .btn, .main-footer { display: none !important; }
            .content-wrapper, .content, .card { margin: 0 !important; box-shadow: none !important; }
        }
    </style>
@stop
