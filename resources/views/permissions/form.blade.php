@extends('layouts.app')

@section('title', $permission->exists ? 'Edit Permission' : 'Add Permission')
@section('page_title', $permission->exists ? 'Edit Permission' : 'Add Permission')
@section('page_intro', 'Define a capability slug, group it sensibly, and describe what it unlocks in the system.')

@section('page')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">{{ $permission->exists ? 'Update Permission Details' : 'Create a New Permission' }}</h3>
        </div>
        <form action="{{ $permission->exists ? route('permissions.update', $permission) : route('permissions.store') }}" method="POST">
            @csrf
            @if ($permission->exists)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Permission Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $permission->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Permission Slug</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', $permission->slug) }}" {{ $permission->is_system ? 'readonly' : '' }}>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Group</label>
                            <input type="text" name="group" class="form-control" value="{{ old('group', $permission->group ?: 'general') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Permission Type</label>
                            <input type="text" class="form-control" value="{{ $permission->is_system ? 'System' : 'Custom' }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $permission->description) }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">{{ $permission->exists ? 'Update Permission' : 'Add Permission' }}</button>
                <a href="{{ route('permissions.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
