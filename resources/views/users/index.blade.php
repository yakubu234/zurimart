@extends('layouts.app')

@section('title', 'Users')
@section('page_title', 'User Management')
@section('page_intro', 'Manage user accounts, assign roles, attach branch ownership, and apply direct permission overrides when needed.')

@push('css')
    <style>
        .direct-permissions-text {
            width: 55ch;
            max-width: 55ch;
            white-space: normal;
            overflow-wrap: anywhere;
        }
    </style>
@endpush

@section('page')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">System Users</h3>
            <div class="card-tools">
                <a href="{{ route('roles.index') }}" class="btn btn-default btn-sm">Roles</a>
                <a href="{{ route('permissions.index') }}" class="btn btn-default btn-sm">Permissions</a>
                <a href="{{ route('users.create') }}" class="btn btn-warning btn-sm">Add User</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Direct Permissions</th>
                        <th>Status</th>
                        <th class="table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong><br>
                                <small class="text-muted">{{ $user->email }}</small><br>
                                <small class="text-muted">{{ $user->phone ?: 'No phone listed' }}</small>
                            </td>
                            <td>
                                @include('partials.badge', ['value' => $user->roleRecord?->name ?? $user->roleKey() ?? 'Unassigned'])
                                <div class="text-muted small mt-1">{{ $user->roleRecord?->description ?: 'No role description available' }}</div>
                            </td>
                            <td>{{ $user->branch?->name ?? 'Not branch-bound' }}</td>
                            <td>
                                @if ($user->permissions->isEmpty())
                                    <span class="text-muted">No overrides</span>
                                @else
                                    <div class="direct-permissions-text">
                                        {{ $user->permissions->pluck('name')->join(', ') }}
                                    </div>
                                @endif
                            </td>
                            <td>@include('partials.badge', ['value' => $user->status])</td>
                            <td class="table-actions-col">
                                <div class="action-buttons">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-info btn-sm action-icon-btn" title="Edit user" aria-label="Edit user">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete this user account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm action-icon-btn" title="Delete user" aria-label="Delete user">
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
