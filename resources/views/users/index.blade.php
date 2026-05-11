@extends('layouts.app')

@section('title', 'Users & Roles')
@section('page_title', 'Users and Roles')
@section('page_intro', 'This is now aligned to the bakery access model so it reads like an actual admin permission view instead of a mock table.')

@section('page')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">System Users</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong><br>
                                <small class="text-muted">{{ $user->phone ?: 'No phone listed' }}</small>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>@include('partials.badge', ['value' => $user->role])</td>
                            <td>{{ $user->branch?->name ?? 'Not branch-bound' }}</td>
                            <td>@include('partials.badge', ['value' => $user->status])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
