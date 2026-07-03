@extends('layouts.app')

@section('title', 'Roles')
@section('page_title', 'Role Management')
@section('page_intro', 'Define reusable access profiles and attach permission bundles that can be assigned to users.')

@section('page')
    <div class="card card-secondary">
        <div class="card-header">
            <h3 class="card-title">Roles</h3>
            <div class="card-tools">
                <a href="{{ route('permissions.index') }}" class="btn btn-default btn-sm">Permissions</a>
                <a href="{{ route('roles.create') }}" class="btn btn-warning btn-sm">Add Role</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Slug</th>
                        <th>Permissions</th>
                        <th>Users</th>
                        <th>Type</th>
                        <th class="table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                <strong>{{ $role->name }}</strong><br>
                                <small class="text-muted">{{ $role->description ?: 'No description' }}</small>
                            </td>
                            <td>{{ $role->slug }}</td>
                            <td>{{ $role->permissions_count }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>@include('partials.badge', ['value' => $role->is_system ? 'system' : 'custom'])</td>
                            <td class="table-actions-col">
                                <div class="action-buttons">
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-info btn-sm action-icon-btn" title="Edit role" aria-label="Edit role">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete this role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm action-icon-btn" title="Delete role" aria-label="Delete role" @disabled($role->is_system)>
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
