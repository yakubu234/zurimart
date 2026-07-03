@extends('layouts.app')

@section('title', 'Raw Material Inventory')
@section('page_title', 'Raw Material Inventory')
@section('page_intro', 'Track materials received and used by each branch, monitor current balances, and spot low stock early.')

@section('page')
    @php
        $formatQuantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
        $canManageAllInventory = auth()->user()?->canManageAllInventory();
        $canEditMovements = auth()->user()?->hasPermission('edit-inventory-movements');
        $canDeleteMovements = auth()->user()?->hasPermission('delete-inventory-movements');
    @endphp

    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Branch Inventory</h3>
            <div class="card-tools">
                <button
                    type="button"
                    class="btn btn-primary btn-sm"
                    data-toggle="modal"
                    data-target="#recordMaterialModal"
                    @disabled($allStockRows->isEmpty())
                >
                    Record Material Activity
                </button>
                @if ($canManageAllInventory)
                    <a href="{{ route('inventory.materials.index') }}" class="btn btn-warning btn-sm ml-1">
                        Manage Raw Materials
                    </a>
                @endif
            </div>
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

    <div class="card card-success">
        <div class="card-header">
            <h3 class="card-title">{{ $selectedBranch->name }} Stock Balances</h3>
            <div class="card-tools">
                <form method="GET" action="{{ route('inventory.index') }}" class="form-inline">
                    <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                    <input type="hidden" name="activity_per_page" value="{{ $activityPerPage }}">
                    <input type="hidden" name="activity_page" value="{{ $recentMovements->currentPage() }}">
                    <label for="stock_per_page" class="mr-2 mb-0 font-weight-normal">Rows</label>
                    <select id="stock_per_page" name="stock_per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach ($pageSizes as $pageSize)
                            <option value="{{ $pageSize }}" @selected($stockPerPage === $pageSize)>{{ $pageSize }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
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
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
            <span class="text-muted">
                Showing {{ $stockRows->firstItem() ?? 0 }} to {{ $stockRows->lastItem() ?? 0 }} of {{ $stockRows->total() }}
            </span>
            @if ($stockRows->hasPages())
                <div>{{ $stockRows->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="recordMaterialModal" tabindex="-1" role="dialog" aria-labelledby="recordMaterialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('inventory.movements.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recordMaterialModalLabel">Record Material Received or Used</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="raw_material_id">Raw Material</label>
                            <select id="raw_material_id" name="raw_material_id" class="form-control" required>
                                <option value="">Select material</option>
                                @foreach ($allStockRows as $row)
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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Inventory Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">{{ $selectedBranch->name }} Recent Material Activity</h3>
            <div class="card-tools">
                <form method="GET" action="{{ route('inventory.index') }}" class="form-inline">
                    <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                    <input type="hidden" name="stock_per_page" value="{{ $stockPerPage }}">
                    <input type="hidden" name="stock_page" value="{{ $stockRows->currentPage() }}">
                    <label for="activity_per_page" class="mr-2 mb-0 font-weight-normal">Rows</label>
                    <select id="activity_per_page" name="activity_per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach ($pageSizes as $pageSize)
                            <option value="{{ $pageSize }}" @selected($activityPerPage === $pageSize)>{{ $pageSize }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
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
                        @if ($canEditMovements || $canDeleteMovements)
                            <th class="table-actions-col">Actions</th>
                        @endif
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
                            @if ($canEditMovements || $canDeleteMovements)
                                <td class="table-actions-col">
                                    <div class="action-buttons">
                                        @if ($canEditMovements)
                                            <button
                                                type="button"
                                                class="btn btn-info btn-sm action-icon-btn"
                                                title="Edit inventory activity"
                                                aria-label="Edit inventory activity"
                                                data-toggle="modal"
                                                data-target="#editMovementModal"
                                                data-movement-id="{{ $movement->id }}"
                                                data-update-url="{{ route('inventory.movements.update', $movement) }}"
                                                data-raw-material-id="{{ $movement->raw_material_id }}"
                                                data-movement-type="{{ $movement->movement_type }}"
                                                data-quantity="{{ $movement->quantity }}"
                                                data-movement-date="{{ $movement->movement_date->toDateString() }}"
                                                data-notes="{{ $movement->notes }}"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($canDeleteMovements)
                                            <form action="{{ route('inventory.movements.destroy', $movement) }}" method="POST" onsubmit="return confirm('Delete this inventory activity permanently?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm action-icon-btn" title="Delete inventory activity" aria-label="Delete inventory activity">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + (($canEditMovements || $canDeleteMovements) ? 1 : 0) }}" class="text-center text-muted py-4">No inventory activity has been recorded for this branch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
            <span class="text-muted">
                Showing {{ $recentMovements->firstItem() ?? 0 }} to {{ $recentMovements->lastItem() ?? 0 }} of {{ $recentMovements->total() }}
            </span>
            @if ($recentMovements->hasPages())
                <div>{{ $recentMovements->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>

    @if ($canEditMovements)
        <div class="modal fade" id="editMovementModal" tabindex="-1" role="dialog" aria-labelledby="editMovementModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="editMovementForm" method="POST">
                        @csrf
                        @method('PUT')
                        <input id="editing_movement_id" type="hidden" name="editing_movement_id">
                        <div class="modal-header bg-info">
                            <h5 class="modal-title" id="editMovementModalLabel">Edit Inventory Activity</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="edit_raw_material_id">Raw Material</label>
                                <select id="edit_raw_material_id" name="raw_material_id" class="form-control" required>
                                    @foreach ($movementMaterials as $material)
                                        <option value="{{ $material->id }}">
                                            {{ $material->name }} ({{ $material->unit }}){{ $material->is_active ? '' : ' — inactive' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="edit_movement_type">Activity</label>
                                        <select id="edit_movement_type" name="movement_type" class="form-control" required>
                                            <option value="received">Received into branch</option>
                                            <option value="used">Used by branch</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="edit_quantity">Quantity</label>
                                        <input id="edit_quantity" type="number" name="quantity" class="form-control" min="0.001" step="0.001" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="edit_movement_date">Date</label>
                                <input id="edit_movement_date" type="date" name="movement_date" class="form-control" max="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="form-group mb-0">
                                <label for="edit_notes">Purpose / Notes</label>
                                <textarea id="edit_notes" name="notes" class="form-control" rows="2" maxlength="1000"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-info">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endsection

@if (auth()->user()?->hasPermission('edit-inventory-movements'))
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                $('#editMovementModal').on('show.bs.modal', function (event) {
                    const button = $(event.relatedTarget);

                    $('#editMovementForm').attr('action', button.data('update-url'));
                    $('#editing_movement_id').val(button.data('movement-id'));
                    $('#edit_raw_material_id').val(String(button.data('raw-material-id')));
                    $('#edit_movement_type').val(button.data('movement-type'));
                    $('#edit_quantity').val(button.data('quantity'));
                    $('#edit_movement_date').val(button.data('movement-date'));
                    $('#edit_notes').val(button.attr('data-notes') || '');
                });

                @if ($errors->any() && old('editing_movement_id'))
                    const trigger = $('[data-movement-id="{{ old('editing_movement_id') }}"]');

                    if (trigger.length) {
                        trigger.trigger('click');
                        $('#edit_raw_material_id').val(@json((string) old('raw_material_id')));
                        $('#edit_movement_type').val(@json(old('movement_type')));
                        $('#edit_quantity').val(@json(old('quantity')));
                        $('#edit_movement_date').val(@json(old('movement_date')));
                        $('#edit_notes').val(@json(old('notes')));
                    }
                @endif
            });
        </script>
    @endpush
@endif

@if ($errors->any() && old('branch_id'))
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                $('#recordMaterialModal').modal('show');
            });
        </script>
    @endpush
@endif
