@extends('layouts.app')

@section('title', $branch->exists ? 'Edit Branch' : 'Add Branch')
@section('page_title', $branch->exists ? 'Edit Branch' : 'Add Branch')
@section('page_intro', 'Maintain branch code, manager details, contact information, capacity, and order availability.')

@section('page')
    <div class="card card-warning card-outline">
        <div class="card-header">
            <h3 class="card-title">{{ $branch->exists ? 'Update Branch Details' : 'Create a New Branch' }}</h3>
        </div>
        <form action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}" method="POST">
            @csrf
            @if ($branch->exists)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Branch Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $branch->code) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Branch Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $branch->name) }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Manager Name</label>
                            <input type="text" name="manager_name" class="form-control" value="{{ old('manager_name', $branch->manager_name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $branch->email) }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>WhatsApp Phone</label>
                            <input type="text" name="whatsapp_phone" class="form-control" value="{{ old('whatsapp_phone', $branch->whatsapp_phone) }}">
                            <small class="text-muted">Branch order alerts will use this WhatsApp number first, then fall back to the regular phone number.</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Daily Capacity Units</label>
                            <input type="number" name="daily_capacity_units" class="form-control" value="{{ old('daily_capacity_units', $branch->daily_capacity_units) }}" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $branch->address) }}">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="available" @selected(old('status', $branch->status ?: 'available') === 'available')>Available</option>
                        <option value="overly_booked" @selected(old('status', $branch->status) === 'overly_booked')>Overly Booked</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Branch Notification Preferences</label>
                    <div class="row">
                        @foreach ($notificationEvents as $eventKey => $label)
                            <div class="col-md-6">
                                <div class="border rounded p-3 mb-3">
                                    <strong class="d-block mb-2">{{ $label }}</strong>
                                    <div class="form-check mb-2">
                                        <input type="hidden" name="notification_preferences[{{ $eventKey }}][email]" value="0">
                                        <input
                                            type="checkbox"
                                            name="notification_preferences[{{ $eventKey }}][email]"
                                            value="1"
                                            class="form-check-input"
                                            id="branch_{{ $eventKey }}_email"
                                            @checked(filter_var(data_get(old('notification_preferences', $branch->notification_preferences ?? []), "{$eventKey}.email", true), FILTER_VALIDATE_BOOL))
                                        >
                                        <label class="form-check-label" for="branch_{{ $eventKey }}_email">Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="hidden" name="notification_preferences[{{ $eventKey }}][whatsapp]" value="0">
                                        <input
                                            type="checkbox"
                                            name="notification_preferences[{{ $eventKey }}][whatsapp]"
                                            value="1"
                                            class="form-check-input"
                                            id="branch_{{ $eventKey }}_whatsapp"
                                            @checked(filter_var(data_get(old('notification_preferences', $branch->notification_preferences ?? []), "{$eventKey}.whatsapp", true), FILTER_VALIDATE_BOOL))
                                        >
                                        <label class="form-check-label" for="branch_{{ $eventKey }}_whatsapp">WhatsApp</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">Turn off any event/channel pair here if this branch should stop receiving that alert.</small>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">{{ $branch->exists ? 'Update Branch' : 'Add Branch' }}</button>
                <a href="{{ route('branches.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
