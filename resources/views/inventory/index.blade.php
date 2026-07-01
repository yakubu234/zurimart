@extends('layouts.app')

@section('title', 'Raw Material Inventory')
@section('page_title', 'Raw Material Inventory')
@section('page_intro', 'Track materials received and used by each branch, monitor current balances, and spot low stock early.')

@section('page')
    @php
        $formatQuantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
        $canManageAllInventory = auth()->user()?->canManageAllInventory();
    @endphp

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Branch Inventory</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.index') }}">
                <div class="row">
                    <div class="col-md-9">
                        <div class="form-group mb-md-0">
                            <label for="branch_id_filter">Branch</label>
                            <select id="branch_id_filter" name="branch_id" class="form-control" {{ auth()->user()?->isInventoryRestricted() ? 'disabled' : '' }}>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($selectedBranch->id === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @if (auth()->user()?->isInventoryRestricted())
                                <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                                <small class="form-text text-muted">Your account is restricted to this branch.</small>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-default w-100">Load Inventory</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $selectedBranch->name }} Stock Balances</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Raw Material</th>
                                <th>Available</th>
                                <th>Low-Stock Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockRows as $row)
                                <tr>
                                    <td>{{ $row['material']->code }}</td>
                                    <td>{{ $row['material']->name }}</td>
                                    <td><strong>{{ $formatQuantity($row['balance']) }} {{ $row['material']->unit }}</strong></td>
                                    <td>{{ $formatQuantity($row['material']->low_stock_threshold) }} {{ $row['material']->unit }}</td>
                                    <td>
                                        <span class="badge {{ $row['is_low'] ? 'badge-danger' : 'badge-success' }}">
                                            {{ $row['is_low'] ? 'Low stock' : 'In stock' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No active raw materials have been added yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Record Material Received or Used</h3>
                </div>
                <form action="{{ route('inventory.movements.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="raw_material_id">Raw Material</label>
                            <select id="raw_material_id" name="raw_material_id" class="form-control" required>
                                <option value="">Select material</option>
                                @foreach ($stockRows as $row)
                                    <option value="{{ $row['material']->id }}" @selected((string) old('raw_material_id') === (string) $row['material']->id)>
                                        {{ $row['material']->name }} ({{ $row['material']->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="movement_type">Activity</label>
                                    <select id="movement_type" name="movement_type" class="form-control" required>
                                        <option value="received" @selected(old('movement_type') === 'received')>Received into branch</option>
                                        <option value="used" @selected(old('movement_type') === 'used')>Used by branch</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity">Quantity</label>
                                    <input id="quantity" type="number" name="quantity" class="form-control" value="{{ old('quantity') }}" min="0.001" step="0.001" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="movement_date">Date</label>
                            <input id="movement_date" type="date" name="movement_date" class="form-control" value="{{ old('movement_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="notes">Purpose / Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="1000" placeholder="For example: Used for today's bread production">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" @disabled($stockRows->isEmpty())>Save Inventory Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $selectedBranch->name }} Recent Material Activity</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Raw Material</th>
                        <th>Activity</th>
                        <th>Quantity</th>
                        <th>Purpose / Notes</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentMovements as $movement)
                        <tr>
                            <td>{{ $movement->movement_date->format('d M Y') }}</td>
                            <td>{{ $movement->rawMaterial->name }}</td>
                            <td>
                                <span class="badge {{ $movement->movement_type === 'received' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $movement->movement_type === 'received' ? 'Received' : 'Used' }}
                                </span>
                            </td>
                            <td>{{ $formatQuantity($movement->quantity) }} {{ $movement->rawMaterial->unit }}</td>
                            <td>{{ $movement->notes ?: '—' }}</td>
                            <td>{{ $movement->recorder?->name ?: 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No inventory activity has been recorded for this branch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($canManageAllInventory)
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Raw Material Catalogue</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('inventory.materials.store') }}" method="POST" class="mb-4">
                    @csrf
                    <div class="row">
                        <div class="col-md-2"><input type="text" name="code" class="form-control" placeholder="Code" required></div>
                        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Material name" required></div>
                        <div class="col-md-2"><input type="text" name="unit" class="form-control" placeholder="Unit (kg, bags)" required></div>
                        <div class="col-md-3"><input type="number" name="low_stock_threshold" class="form-control" min="0" step="0.001" placeholder="Low-stock level" required></div>
                        <div class="col-md-2">
                            <input type="hidden" name="is_active" value="1">
                            <button type="submit" class="btn btn-warning w-100">Add Material</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Unit</th>
                                <th>Low-Stock Level</th>
                                <th>Active</th>
                                <th class="table-actions-col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($materials as $material)
                                <tr>
                                    <td><input form="material-{{ $material->id }}" type="text" name="code" class="form-control" value="{{ $material->code }}" required></td>
                                    <td><input form="material-{{ $material->id }}" type="text" name="name" class="form-control" value="{{ $material->name }}" required></td>
                                    <td><input form="material-{{ $material->id }}" type="text" name="unit" class="form-control" value="{{ $material->unit }}" required></td>
                                    <td><input form="material-{{ $material->id }}" type="number" name="low_stock_threshold" class="form-control" value="{{ $material->low_stock_threshold }}" min="0" step="0.001" required></td>
                                    <td class="text-center">
                                        <input form="material-{{ $material->id }}" type="hidden" name="is_active" value="0">
                                        <input form="material-{{ $material->id }}" type="checkbox" name="is_active" value="1" @checked($material->is_active)>
                                    </td>
                                    <td>
                                        <form id="material-{{ $material->id }}" action="{{ route('inventory.materials.update', $material) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-info btn-sm">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No raw materials yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
