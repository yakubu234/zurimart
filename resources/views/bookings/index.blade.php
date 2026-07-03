@extends('layouts.app')

@section('title', 'Advance Bookings')
@section('page_title', 'Advance Bookings')
@section('page_intro', 'Use this view to understand wholesale reservations and future production commitments before they become same-day branch pressure.')

@section('page')
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">Reserved Production Slots</h3>
            <div class="card-tools">
                <a href="{{ route('orders.create') }}" class="btn btn-warning btn-sm">Create Booking</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Units</th>
                        <th>Production Date</th>
                        <th>Assigned Branch</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->order_number }}</td>
                            <td>{{ $booking->customer_name }}</td>
                            <td>{{ $booking->total_units }}</td>
                            <td>{{ $booking->scheduled_for->format('d M Y') }}</td>
                            <td>{{ $booking->branch?->name }}</td>
                            <td>@include('partials.badge', ['value' => $booking->status])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
