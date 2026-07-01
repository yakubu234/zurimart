@extends('layouts.app')

@section('title', 'Audit Trail')
@section('page_title', 'System Audit Trail')
@section('page_intro', 'Review who created, changed, or deleted records across the application, including before-and-after values.')

@section('page')
    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($logs->total()) }}</h3>
                    <p>Matching Activities</p>
                </div>
                <div class="icon"><i class="fas fa-history"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($todayCount) }}</h3>
                    <p>Activities Today</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $modelTypes->count() }}</h3>
                    <p>Record Types Tracked</p>
                </div>
                <div class="icon"><i class="fas fa-database"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Filter Audit Logs</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('audit-logs.index') }}">
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input id="search" type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Record, ID, description, or user">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <div class="form-group">
                            <label for="action">Action</label>
                            <select id="action" name="action" class="form-control">
                                <option value="">All actions</option>
                                @foreach (['created', 'updated', 'deleted', 'logged_in', 'logged_out'] as $action)
                                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ \Illuminate\Support\Str::headline($action) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="auditable_type">Record Type</label>
                            <select id="auditable_type" name="auditable_type" class="form-control">
                                <option value="">All record types</option>
                                @foreach ($modelTypes as $modelType)
                                    <option value="{{ $modelType }}" @selected(request('auditable_type') === $modelType)>
                                        {{ \Illuminate\Support\Str::headline(class_basename($modelType)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="user_id">User</label>
                            <select id="user_id" name="user_id" class="form-control">
                                <option value="">All users</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="branch_id">Branch</label>
                            <select id="branch_id" name="branch_id" class="form-control">
                                <option value="">All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input id="date_from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input id="date_to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="{{ route('audit-logs.index') }}" class="btn btn-default">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recorded Activity</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Record</th>
                        <th>Branch</th>
                        <th>Request</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td>
                                {{ $log->user->name }}
                                @if ($log->ip_address)
                                    <small class="d-block text-muted">{{ $log->ip_address }}</small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $actionClass = match ($log->action) {
                                        'created' => 'badge-success',
                                        'updated' => 'badge-warning',
                                        'deleted' => 'badge-danger',
                                        'logged_in' => 'badge-primary',
                                        'logged_out' => 'badge-secondary',
                                        default => 'badge-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $actionClass }}">{{ \Illuminate\Support\Str::headline($log->action) }}</span>
                            </td>
                            <td>
                                <strong>{{ \Illuminate\Support\Str::headline(class_basename($log->auditable_type)) }}</strong>
                                <small class="d-block text-muted">{{ $log->subject_label ?: "#{$log->auditable_id}" }}</small>
                            </td>
                            <td>{{ $log->branch?->name ?: 'Global' }}</td>
                            <td>
                                {{ $log->method ?: 'SYSTEM' }}
                                @if ($log->url)
                                    <small class="d-block text-muted text-truncate" style="max-width: 180px;" title="{{ $log->url }}">{{ $log->url }}</small>
                                @endif
                            </td>
                            <td style="min-width: 260px;">
                                <details>
                                    <summary class="text-primary" style="cursor: pointer;">View details</summary>
                                    <div class="mt-2">
                                        <strong>{{ $log->description }}</strong>
                                        @if ($log->old_values)
                                            <small class="d-block mt-2 text-muted">Before</small>
                                            <pre class="bg-light border rounded p-2 mb-2 text-wrap">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @endif
                                        @if ($log->new_values)
                                            <small class="d-block text-muted">After</small>
                                            <pre class="bg-light border rounded p-2 mb-0 text-wrap">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No audit activity matches the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
