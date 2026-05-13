@extends('layouts.app')

@section('title', 'Branches')
@section('page_title', 'Branch Management')
@section('page_intro', 'Add, edit, and review production branches with clear capacity tracking and operational status.')

@section('page')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Production Branches</h3>
            <div class="card-tools">
                <a href="{{ route('branches.create') }}" class="btn btn-warning btn-sm">Add Branch</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Branch</th>
                        <th>Manager</th>
                        <th>Contact</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th class="table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($branches as $branch)
                        @php
                            $slot = $branch->capacitySlots->first();
                            $used = $slot?->locked_units ?? 0;
                            $capacity = $slot?->capacity_units ?? $branch->daily_capacity_units;
                        @endphp
                        <tr>
                            <td>{{ $branch->code }}</td>
                            <td>
                                <strong>{{ $branch->name }}</strong><br>
                                <small class="text-muted">{{ $branch->address }}</small>
                            </td>
                            <td>{{ $branch->manager_name }}</td>
                            <td>{{ $branch->phone }}<br><small class="text-muted">{{ $branch->email }}</small></td>
                            <td>{{ $used }} / {{ $capacity }} units</td>
                            <td>@include('partials.badge', ['value' => $branch->status])</td>
                            <td class="table-actions-col">
                                <div class="action-buttons">
                                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-info btn-sm action-icon-btn" title="Edit branch" aria-label="Edit branch">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('branches.destroy', $branch) }}" method="POST" onsubmit="return confirm('Delete this branch?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm action-icon-btn" title="Delete branch" aria-label="Delete branch">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
