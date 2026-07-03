@extends('layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Add User')
@section('page_title', $user->exists ? 'Edit User' : 'Add User')
@section('page_intro', 'Maintain identity, role assignment, branch ownership, account status, and direct permission overrides.')

@section('page')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">{{ $user->exists ? 'Update User Details' : 'Create a New User' }}</h3>
        </div>
        <form action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" method="POST">
            @csrf
            @if ($user->exists)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role_id" class="form-control" required>
                                <option value="">Select role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Assigned Branch</label>
                            <select name="branch_id" class="form-control">
                                <option value="">No branch assignment</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Required for production branch managers.</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="active" @selected(old('status', $user->status ?: 'active') === 'active')>Active</option>
                                <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Password {{ $user->exists ? '(leave blank to keep current)' : '' }}</label>
                            <input type="password" name="password" class="form-control" {{ $user->exists ? '' : 'required' }}>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" {{ $user->exists ? '' : 'required' }}>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Direct Permission Overrides</label>
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
                                                id="permission_{{ $permission->id }}"
                                                @checked(collect(old('permission_ids', $user->permissions->pluck('id')->all()))->contains($permission->id))
                                            >
                                            <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">Use direct permissions sparingly. Prefer role-based access for most users.</small>
                </div>

                <div class="form-group">
                    <label>User Notification Preferences</label>
                    <div class="row">
                        @foreach ($notificationEvents as $eventKey => $label)
                            <div class="col-md-3">
                                <div class="border rounded p-3 mb-3">
                                    <strong class="d-block mb-2">{{ $label }}</strong>
                                    <div class="form-check mb-2">
                                        <input type="hidden" name="notification_preferences[{{ $eventKey }}][email]" value="0">
                                        <input
                                            type="checkbox"
                                            name="notification_preferences[{{ $eventKey }}][email]"
                                            value="1"
                                            class="form-check-input"
                                            id="user_{{ $eventKey }}_email"
                                            @checked(filter_var(data_get(old('notification_preferences', $user->notification_preferences ?? []), "{$eventKey}.email", true), FILTER_VALIDATE_BOOL))
                                        >
                                        <label class="form-check-label" for="user_{{ $eventKey }}_email">Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="hidden" name="notification_preferences[{{ $eventKey }}][whatsapp]" value="0">
                                        <input
                                            type="checkbox"
                                            name="notification_preferences[{{ $eventKey }}][whatsapp]"
                                            value="1"
                                            class="form-check-input"
                                            id="user_{{ $eventKey }}_whatsapp"
                                            @checked(filter_var(data_get(old('notification_preferences', $user->notification_preferences ?? []), "{$eventKey}.whatsapp", true), FILTER_VALIDATE_BOOL))
                                        >
                                        <label class="form-check-label" for="user_{{ $eventKey }}_whatsapp">WhatsApp</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">Use this to disable specific alerts for a particular admin, manager, or branch-bound user.</small>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">{{ $user->exists ? 'Update User' : 'Add User' }}</button>
                <a href="{{ route('users.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
