@extends('layouts.app')

@section('title', 'Orders')
@section('page_title', 'Orders Management')
@section('page_intro', 'Review smart branch-tagging, retail versus wholesale pricing, and manager approval actions in one AdminLTE table.')

@section('page')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Bakery Orders</h3>
            <div class="card-tools">
                <a href="{{ route('orders.create') }}" class="btn btn-warning btn-sm">Create New Order</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Demand</th>
                        <th>Units</th>
                        <th>Branch</th>
                        <th>Tier</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->order_number }}</strong><br>
                                <small class="text-muted">{{ $order->scheduled_for->format('d M Y') }}</small>
                            </td>
                            <td>
                                {{ $order->customer_name }}<br>
                                <small class="text-muted">{{ ucwords(str_replace('_', ' ', $order->customer_type)) }}</small>
                            </td>
                            <td>@include('partials.badge', ['value' => $order->demand_type])</td>
                            <td>{{ $order->total_units }}</td>
                            <td>{{ $order->branch?->name ?? 'Unassigned' }}</td>
                            <td>@include('partials.badge', ['value' => $order->pricing_tier])</td>
                            <td>N{{ number_format($order->total_amount, 0) }}</td>
                            <td>@include('partials.badge', ['value' => $order->status])</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-default">Receipt</a>
                                    @if ($order->status === 'pending')
                                        <form action="{{ route('orders.accept', $order) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-success">Accept</button>
                                        </form>
                                        <form action="{{ route('orders.reject', $order) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="rejection_reason" value="Branch manager rejected the order due to live oven capacity.">
                                            <button type="submit" class="btn btn-danger">Reject</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <span class="text-muted">Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders</span>
            <div>
                @if ($orders->onFirstPage())
                    <span class="btn btn-default btn-sm disabled">Previous</span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}" class="btn btn-default btn-sm">Previous</a>
                @endif
                @if ($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}" class="btn btn-default btn-sm">Next</a>
                @else
                    <span class="btn btn-default btn-sm disabled">Next</span>
                @endif
            </div>
        </div>
    </div>
@endsection
