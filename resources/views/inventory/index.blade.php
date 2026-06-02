@extends('layouts.app')

@section('title', 'Branch Inventory')
@section('page_title', 'Branch Inventory')
@section('page_intro', 'Record opening stock, daily production, sales, adjustments, and closing units per branch product.')

@section('page')
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Inventory Filters</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.index') }}">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Branch</label>
                            <select name="branch_id" class="form-control" {{ auth()->user()?->isInventoryRestricted() ? 'disabled' : '' }}>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($selectedBranch->id === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @if (auth()->user()?->isInventoryRestricted())
                                <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Inventory Date</label>
                            <input type="date" name="inventory_date" class="form-control" value="{{ $inventoryDate }}">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-default w-100">Load Inventory Sheet</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <form action="{{ route('inventory.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
        <input type="hidden" name="inventory_date" value="{{ $inventoryDate }}">

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $selectedBranch->name }} Inventory Sheet for {{ \Carbon\Carbon::parse($inventoryDate)->format('d M Y') }}</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Opening</th>
                            <th>Produced</th>
                            <th>Sold</th>
                            <th>Adjustment</th>
                            <th>Closing</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['product']->name }}</td>
                                <td>{{ $row['product']->productCategory?->name ?? $row['product']->category }}</td>
                                <td><input type="number" min="0" inputmode="numeric" name="rows[{{ $row['product']->id }}][opening_units]" class="form-control quantity-input" value="{{ old("rows.{$row['product']->id}.opening_units", $row['opening_units']) }}"></td>
                                <td><input type="number" min="0" inputmode="numeric" name="rows[{{ $row['product']->id }}][produced_units]" class="form-control quantity-input" value="{{ old("rows.{$row['product']->id}.produced_units", $row['produced_units']) }}"></td>
                                <td><input type="number" min="0" inputmode="numeric" name="rows[{{ $row['product']->id }}][sold_units]" class="form-control quantity-input" value="{{ old("rows.{$row['product']->id}.sold_units", $row['sold_units']) }}"></td>
                                <td><input type="number" inputmode="numeric" name="rows[{{ $row['product']->id }}][adjustment_units]" class="form-control quantity-input" value="{{ old("rows.{$row['product']->id}.adjustment_units", $row['adjustment_units']) }}"></td>
                                <td><span class="badge badge-info">{{ $row['closing_units'] }} units</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">Save Inventory Sheet</button>
            </div>
        </div>
    </form>
@endsection
