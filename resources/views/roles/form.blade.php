@extends('layouts.app')

@section('title', $role->exists ? 'Edit Role' : 'Add Role')
@section('page_title', $role->exists ? 'Edit Role' : 'Add Role')
@section('page_intro', 'Manage role identity and assign the permission bundle users will inherit from this role.')

@section('page')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">{{ $role->exists ? 'Update Role Details' : 'Create a New Role' }}</h3>
        </div>
        <form action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}" method="POST">
            @csrf
            @if ($role->exists)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Role Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Role Slug</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', $role->slug) }}" {{ $role->is_system ? 'readonly' : '' }}>
                            <small class="text-muted">Used internally for access matching.</small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $role->description) }}</textarea>
                </div>
                <div class="form-group">
                    <label>Assigned Permissions</label>
                    <div class="row">
                        @foreach ($permissions as $group => $items)
                            <div class="col-md-3">
                                <div class="border rounded p-3 mb-3">
                                    <strong class="d-block text-capitalize mb-2">{{ str_replace('_', ' ', $group) }}</strong>
                                    @foreach ($items as $permission)
                                        <div class="form-check mb-2">
                                            <input
                                                type="checkbox"
                                                name="permission_ids[]"
                                                value="{{ $permission->id }}"
                                                class="form-check-input"
                                                id="role_permission_{{ $permission->id }}"
                                                @checked(collect(old('permission_ids', $role->permissions->pluck('id')->all()))->contains($permission->id))
                                            >
                                            <label class="form-check-label" for="role_permission_{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">{{ $role->exists ? 'Update Role' : 'Add Role' }}</button>
                <a href="{{ route('roles.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
