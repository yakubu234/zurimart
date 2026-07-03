@extends('layouts.app')

@section('title', 'Reports')
@section('page_title', 'Reports and Analytics')
@section('page_intro', 'AdminLTE summary cards and progress views make the bakery numbers easier to read than the custom layout did.')

@section('page')
    <div class="row">
        <div class="col-lg-7">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Weekly Sales Comparison</h3>
                </div>
                <div class="card-body">
                    @php $salesMax = max(1, $salesTrend->max(fn ($day) => max($day['retail'], $day['wholesale']))); @endphp
                    @foreach ($salesTrend as $day)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $day['day'] }}</strong>
                                <span class="text-muted">{{ $day['retail'] + $day['wholesale'] }} total units</span>
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-primary" style="width: {{ ($day['retail'] / $salesMax) * 100 }}%"></div>
                            </div>
                            <div class="progress progress-sm mt-1">
                                <div class="progress-bar bg-warning" style="width: {{ ($day['wholesale'] / $salesMax) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Branch Order Distribution</h3>
                </div>
                <div class="card-body">
                    @php $distributionMax = max(1, $branchPerformance->max('orders_count')); @endphp
                    @foreach ($branchPerformance as $row)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $row->name }}</strong>
                                <span>{{ $row->orders_count }} orders</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: {{ ($row->orders_count / $distributionMax) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Branch Daily Report Summary</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Opening</th>
                                <th>Produced</th>
                                <th>Sold</th>
                                <th>Closing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inventoryPerformance as $row)
                                <tr>
                                    <td>{{ $row['branch']->name }}</td>
                                    <td>{{ $row['opening_units'] }}</td>
                                    <td>{{ $row['produced_units'] }}</td>
                                    <td>{{ $row['sold_units'] }}</td>
                                    <td><strong>{{ $row['closing_units'] }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">72-Hour Stale Stock</h3>
                </div>
                <div class="card-body">
                    @forelse ($staleStockBatches as $batch)
                        <div class="mb-3 pb-3 border-bottom">
                            <strong>{{ $batch->branch?->name }}</strong><br>
                            <span>{{ $batch->product?->name }}</span><br>
                            <small class="text-muted">{{ $batch->remaining_units }} units unsold since {{ $batch->produced_date->format('d M Y') }}</small>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No stale stock batches older than 72 hours right now.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
