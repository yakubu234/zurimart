@extends('layouts.app')

@section('title', 'Permissions')
@section('page_title', 'Permission Management')
@section('page_intro', 'Maintain the system capabilities that can be attached to roles or granted directly to users.')

@section('page')
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">Permissions</h3>
            <div class="card-tools">
                <a href="{{ route('roles.index') }}" class="btn btn-default btn-sm">Roles</a>
                <a href="{{ route('permissions.create') }}" class="btn btn-warning btn-sm">Add Permission</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Group</th>
                        <th>Roles</th>
                        <th>Direct Users</th>
                        <th>Type</th>
                        <th class="table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $permission)
                        <tr>
                            <td>
                                <strong>{{ $permission->name }}</strong><br>
                                <small class="text-muted">{{ $permission->description ?: 'No description' }}</small>
                            </td>
                            <td>{{ $permission->slug }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $permission->group)) }}</td>
                            <td>{{ $permission->roles_count }}</td>
                            <td>{{ $permission->users_count }}</td>
                            <td>@include('partials.badge', ['value' => $permission->is_system ? 'system' : 'custom'])</td>
                            <td class="table-actions-col">
                                <div class="action-buttons">
                                    <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-info btn-sm action-icon-btn" title="Edit permission" aria-label="Edit permission">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('permissions.destroy', $permission) }}" method="POST" onsubmit="return confirm('Delete this permission?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm action-icon-btn" title="Delete permission" aria-label="Delete permission" @disabled($permission->is_system)>
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
