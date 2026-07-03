@extends('layouts.app')

@section('title', 'Daily Report')
@section('page_title', 'Daily Branch Report')
@section('page_intro', 'Record each branch product’s opening quantity, daily production, sales, adjustments, and closing quantity.')

@section('page')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Daily Report Filters</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('daily-reports.index') }}">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Branch</label>
                            <select name="branch_id" class="form-control" {{ auth()->user()?->isDailyReportRestricted() ? 'disabled' : '' }}>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($selectedBranch->id === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @if (auth()->user()?->isDailyReportRestricted())
                                <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Report Date</label>
                            <input type="date" name="report_date" class="form-control" value="{{ $reportDate }}">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-default w-100">Load Daily Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <form action="{{ route('daily-reports.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
        <input type="hidden" name="report_date" value="{{ $reportDate }}">

        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">{{ $selectedBranch->name }} Daily Report for {{ \Carbon\Carbon::parse($reportDate)->format('d M Y') }}</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover text-nowrap daily-report-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="quantity-column">Opening</th>
                            <th class="quantity-column">Produced</th>
                            <th class="quantity-column">Sold</th>
                            <th class="quantity-column">Adjustment</th>
                            <th>Closing</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['product']->name }}</td>
                                <td>{{ $row['product']->productCategory?->name ?? $row['product']->category }}</td>
                                <td class="quantity-column"><input type="number" min="0" inputmode="numeric" name="rows[{{ $row['product']->id }}][opening_units]" class="form-control quantity-input" value="{{ old("rows.{$row['product']->id}.opening_units", $row['opening_units']) }}"></td>
                                <td class="quantity-column"><input type="number" min="0" inputmode="numeric" name="rows[{{ $row['product']->id }}][produced_units]" class="form-control quantity-input" value="{{ old("rows.{$row['product']->id}.produced_units", $row['produced_units']) }}"></td>
                                <td class="quantity-column"><input type="number" min="0" inputmode="numeric" name="rows[{{ $row['product']->id }}][sold_units]" class="form-control quantity-input" value="{{ old("rows.{$row['product']->id}.sold_units", $row['sold_units']) }}"></td>
                                <td class="quantity-column"><input type="number" inputmode="numeric" name="rows[{{ $row['product']->id }}][adjustment_units]" class="form-control quantity-input" value="{{ old("rows.{$row['product']->id}.adjustment_units", $row['adjustment_units']) }}"></td>
                                <td><span class="badge badge-info">{{ $row['closing_units'] }} units</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">Save Daily Report</button>
            </div>
        </div>
    </form>
@endsection
