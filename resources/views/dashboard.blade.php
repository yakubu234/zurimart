@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Bakery Operations Dashboard')
@section('page_intro', 'A cleaner AdminLTE control room for production capacity, tagged orders, wholesale activity, and branch performance.')

@section('page')
    <div class="row">
        <div class="col-lg-2 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>N{{ number_format($stats['totalRevenue'], 0) }}</h3>
                    <p>Accepted Revenue</p>
                </div>
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['totalOrders'] }}</h3>
                    <p>Total Orders</p>
                </div>
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['pendingOrders'] }}</h3>
                    <p>Pending Approval</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['activeBranches'] }}</h3>
                    <p>Available Branches</p>
                </div>
                <div class="icon"><i class="fas fa-industry"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $stats['lowStockItems'] }}</h3>
                    <p>Low Stock Items</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $stats['wholesaleShare'] }}%</h3>
                    <p>Wholesale Share</p>
                </div>
                <div class="icon"><i class="fas fa-chart-pie"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Sales Trend Snapshot</h3>
                </div>
                <div class="card-body">
                    @php $trendMax = max(1, $salesTrend->max(fn ($day) => max($day['retail'], $day['wholesale']))); @endphp
                    @foreach ($salesTrend as $day)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $day['day'] }}</strong>
                                <span class="text-muted">Retail {{ $day['retail'] }} units | Wholesale {{ $day['wholesale'] }} units</span>
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-primary" style="width: {{ ($day['retail'] / $trendMax) * 100 }}%"></div>
                            </div>
                            <div class="progress progress-sm mt-1">
                                <div class="progress-bar bg-warning" style="width: {{ ($day['wholesale'] / $trendMax) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Recent Orders</h3>
                    <div class="card-tools">
                        <a href="{{ route('orders.create') }}" class="btn btn-sm btn-warning">Create Order</a>
                        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-default">View All</a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                <th>Tier</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td>
                                        <strong>{{ $order->order_number }}</strong><br>
                                        <small class="text-muted">{{ $order->scheduled_for->format('d M Y') }}</small>
                                    </td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td>{{ $order->branch?->name ?? 'Unassigned' }}</td>
                                    <td>@include('partials.badge', ['value' => $order->pricing_tier])</td>
                                    <td>N{{ number_format($order->total_amount, 0) }}</td>
                                    <td>@include('partials.badge', ['value' => $order->status])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Branch Capacity Today</h3>
                </div>
                <div class="card-body">
                    @foreach ($branches as $row)
                        @php
                            $bar = $row['pct'] >= 95 ? 'bg-danger' : ($row['pct'] >= 75 ? 'bg-warning' : 'bg-success');
                        @endphp
                        <div class="mb-4">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $row['branch']->name }}</strong><br>
                                    <small class="text-muted">{{ $row['branch']->manager_name }}</small>
                                </div>
                                <div>@include('partials.badge', ['value' => $row['branch']->status])</div>
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar {{ $bar }}" style="width: {{ min($row['pct'], 100) }}%"></div>
                            </div>
                            <small class="text-muted">{{ $row['used'] }} / {{ $row['capacity'] }} units locked</small>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Branch Performance</h3>
                </div>
                <div class="card-body">
                    @php $branchMax = max(1, $branchPerformance->max('orders_count')); @endphp
                    @foreach ($branchPerformance as $row)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>{{ $row->name }}</span>
                                <strong>{{ $row->orders_count }} orders</strong>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-info" style="width: {{ ($row->orders_count / $branchMax) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Notifications</h3>
                </div>
                <div class="card-body">
                    @foreach ($notifications as $notification)
                        <div class="callout callout-info py-2">
                            <h5>{{ $notification->title }}</h5>
                            <p class="mb-0">{{ $notification->message }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
