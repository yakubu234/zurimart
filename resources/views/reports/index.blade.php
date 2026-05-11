@extends('layouts.app')

@section('title', 'Reports')
@section('page_title', 'Reports and Analytics')
@section('page_intro', 'AdminLTE summary cards and progress views make the bakery numbers easier to read than the custom layout did.')

@section('page')
    <div class="row">
        <div class="col-lg-7">
            <div class="card card-outline card-info">
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
            <div class="card card-outline card-secondary">
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
@endsection
