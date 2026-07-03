@extends('layouts.app')

@section('title', 'Raw Material Catalogue')
@section('page_title', 'Raw Material Catalogue')
@section('page_intro', 'Add and maintain the raw materials used across branch inventory.')

@push('css')
    <style>
        @media (max-width: 767.98px) {
            .material-create-field {
                margin-bottom: 0.75rem;
            }

            .raw-material-catalogue .material-code-column,
            .raw-material-catalogue .material-unit-column {
                min-width: 150px;
            }

            .raw-material-catalogue .material-name-column {
                min-width: 230px;
            }

            .raw-material-catalogue .form-control {
                width: 100%;
                min-width: 100%;
            }
        }
    </style>
@endpush

@section('page')
    <div class="card card-secondary">
        <div class="card-header">
            <h3 class="card-title">Raw Materials</h3>
            <div class="card-tools">
                <a href="{{ route('inventory.index') }}" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Inventory
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('inventory.materials.store') }}" method="POST" class="mb-4">
                @csrf
                <div class="row">
                    <div class="col-12 col-md-2 material-create-field"><input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="Code" required></div>
                    <div class="col-12 col-md-3 material-create-field"><input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Material name" required></div>
                    <div class="col-12 col-md-2 material-create-field"><input type="text" name="unit" class="form-control" value="{{ old('unit') }}" placeholder="Unit (kg, bags)" required></div>
                    <div class="col-12 col-md-3 material-create-field"><input type="number" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold') }}" min="0" step="0.001" placeholder="Low-stock level" required></div>
                    <div class="col-12 col-md-2">
                        <input type="hidden" name="is_active" value="1">
                        <button type="submit" class="btn btn-warning w-100">Add Material</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered text-nowrap mb-0 raw-material-catalogue">
                    <thead>
                        <tr>
                            <th class="material-code-column">Code</th>
                            <th class="material-name-column">Name</th>
                            <th class="material-unit-column">Unit</th>
                            <th>Low-Stock Level</th>
                            <th>Active</th>
                            <th class="table-actions-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                            <tr>
                                <td class="material-code-column"><input form="material-{{ $material->id }}" type="text" name="code" class="form-control" value="{{ $material->code }}" required></td>
                                <td class="material-name-column"><input form="material-{{ $material->id }}" type="text" name="name" class="form-control" value="{{ $material->name }}" required></td>
                                <td class="material-unit-column"><input form="material-{{ $material->id }}" type="text" name="unit" class="form-control" value="{{ $material->unit }}" required></td>
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
@endsection
