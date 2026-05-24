@extends('layouts.app')

@section('title', 'Integration Settings')
@section('page_title', 'Notification and Integration Settings')
@section('page_intro', 'Super Admin can configure SMTP and WhatsApp credentials here without exposing secrets in the interface or hard-coding them in templates.')

@section('page')
    <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-6">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Email Delivery Settings</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input type="hidden" name="email_enabled" value="0">
                            <input type="checkbox" name="email_enabled" value="1" class="form-check-input" id="email_enabled"
                                @checked(old('email_enabled', filter_var($notificationSettings['notifications.email_enabled'] ?? false, FILTER_VALIDATE_BOOL)))>
                            <label class="form-check-label" for="email_enabled">Enable email notifications</label>
                        </div>
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="email_host" class="form-control" value="{{ old('email_host', $notificationSettings['notifications.email_host'] ?? '') }}">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SMTP Port</label>
                                    <input type="number" name="email_port" class="form-control" value="{{ old('email_port', $notificationSettings['notifications.email_port'] ?? 587) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Encryption</label>
                                    <input type="text" name="email_encryption" class="form-control" value="{{ old('email_encryption', $notificationSettings['notifications.email_encryption'] ?? 'tls') }}" placeholder="tls or ssl">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="email_username" class="form-control" value="{{ old('email_username', $notificationSettings['notifications.email_username'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="email_password" class="form-control" value="">
                            <small class="text-muted">Leave blank to keep the current SMTP password.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>From Address</label>
                                    <input type="email" name="email_from_address" class="form-control" value="{{ old('email_from_address', $notificationSettings['notifications.email_from_address'] ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>From Name</label>
                                    <input type="text" name="email_from_name" class="form-control" value="{{ old('email_from_name', $notificationSettings['notifications.email_from_name'] ?? 'ZuriMart Bakery') }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Admin Alert Email Recipient</label>
                            <input type="email" name="admin_email_recipient" class="form-control" value="{{ old('admin_email_recipient', $notificationSettings['notifications.admin_email_recipient'] ?? '') }}" placeholder="Where low stock and admin alerts should go">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">WhatsApp Delivery Settings</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input type="hidden" name="whatsapp_enabled" value="0">
                            <input type="checkbox" name="whatsapp_enabled" value="1" class="form-check-input" id="whatsapp_enabled"
                                @checked(old('whatsapp_enabled', filter_var($notificationSettings['notifications.whatsapp_enabled'] ?? false, FILTER_VALIDATE_BOOL)))>
                            <label class="form-check-label" for="whatsapp_enabled">Enable WhatsApp notifications</label>
                        </div>
                        <div class="form-group">
                            <label>WhatsApp API URL</label>
                            <input type="url" name="whatsapp_api_url" class="form-control" value="{{ old('whatsapp_api_url', $notificationSettings['notifications.whatsapp_api_url'] ?? '') }}" placeholder="https://your-provider.example/api/send">
                        </div>
                        <div class="form-group">
                            <label>WhatsApp Token</label>
                            <input type="password" name="whatsapp_token" class="form-control" value="">
                            <small class="text-muted">Leave blank to keep the current WhatsApp token.</small>
                        </div>
                        <div class="form-group">
                            <label>Phone Number ID</label>
                            <input type="text" name="whatsapp_phone_number_id" class="form-control" value="{{ old('whatsapp_phone_number_id', $notificationSettings['notifications.whatsapp_phone_number_id'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>From Number</label>
                            <input type="text" name="whatsapp_from_number" class="form-control" value="{{ old('whatsapp_from_number', $notificationSettings['notifications.whatsapp_from_number'] ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Admin Alert WhatsApp Recipient</label>
                            <input type="text" name="admin_whatsapp_recipient" class="form-control" value="{{ old('admin_whatsapp_recipient', $notificationSettings['notifications.admin_whatsapp_recipient'] ?? '') }}" placeholder="e.g. +2348012345678">
                        </div>
                        <div class="alert alert-warning mb-0">
                            Use the API format required by your WhatsApp provider. Branch order alerts are sent to the tagged branch's WhatsApp phone first, then to its normal phone if no WhatsApp number is saved.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Notification Event Rules</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check mb-3">
                                    <input type="hidden" name="event_order_placed" value="0">
                                    <input type="checkbox" name="event_order_placed" value="1" class="form-check-input" id="event_order_placed"
                                        @checked(old('event_order_placed', filter_var($notificationSettings['notifications.event_order_placed'] ?? true, FILTER_VALIDATE_BOOL)))>
                                    <label class="form-check-label" for="event_order_placed">When an order is placed</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="hidden" name="event_order_accepted" value="0">
                                    <input type="checkbox" name="event_order_accepted" value="1" class="form-check-input" id="event_order_accepted"
                                        @checked(old('event_order_accepted', filter_var($notificationSettings['notifications.event_order_accepted'] ?? true, FILTER_VALIDATE_BOOL)))>
                                    <label class="form-check-label" for="event_order_accepted">When an order is accepted</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mb-3">
                                    <input type="hidden" name="event_order_rejected" value="0">
                                    <input type="checkbox" name="event_order_rejected" value="1" class="form-check-input" id="event_order_rejected"
                                        @checked(old('event_order_rejected', filter_var($notificationSettings['notifications.event_order_rejected'] ?? true, FILTER_VALIDATE_BOOL)))>
                                    <label class="form-check-label" for="event_order_rejected">When an order is rejected</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="hidden" name="event_low_stock" value="0">
                                    <input type="checkbox" name="event_low_stock" value="1" class="form-check-input" id="event_low_stock"
                                        @checked(old('event_low_stock', filter_var($notificationSettings['notifications.event_low_stock'] ?? true, FILTER_VALIDATE_BOOL)))>
                                    <label class="form-check-label" for="event_low_stock">When stock becomes low</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mb-3">
                                    <input type="hidden" name="event_branch_overbooked" value="0">
                                    <input type="checkbox" name="event_branch_overbooked" value="1" class="form-check-input" id="event_branch_overbooked"
                                        @checked(old('event_branch_overbooked', filter_var($notificationSettings['notifications.event_branch_overbooked'] ?? true, FILTER_VALIDATE_BOOL)))>
                                    <label class="form-check-label" for="event_branch_overbooked">When a branch becomes overly booked</label>
                                </div>
                                <div class="form-group mt-3">
                                    <label>Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', $notificationSettings['notifications.low_stock_threshold'] ?? 150) }}" min="0">
                                    <small class="form-text text-muted">Any product at or below this value will trigger a low stock alert after stock movement.</small>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mb-0">
                            Recommended enabled events: order placed, order accepted, order rejected, low stock, and branch overly booked. These cover the most important bakery operational handoffs.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-secondary">
            Branch delivery contacts are managed on each branch record, and per-user or per-branch notification opt-outs are managed on the User and Branch screens.
        </div>

        <div class="card">
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">Save Integration Settings</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Notification Attempts</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Channel</th>
                        <th>Recipient</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentNotifications as $notification)
                        <tr>
                            <td>{{ str_replace('_', ' ', $notification->event_key ?: 'general') }}</td>
                            <td>{{ strtoupper($notification->channel) }}</td>
                            <td>{{ $notification->recipient ?: 'Not stored' }}</td>
                            <td>{{ $notification->title }}</td>
                            <td>@include('partials.badge', ['value' => $notification->status])</td>
                            <td>{{ $notification->sent_at?->format('d M Y H:i') ?: 'Pending' }}</td>
                            <td>{{ $notification->error_message ?: 'None' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
