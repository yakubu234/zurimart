@extends('adminlte::page')

@section('title', trim($__env->yieldContent('title', 'ZuriMart Bakery')))

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-8">
            <h1 class="m-0">@yield('page_title', 'ZuriMart Bakery')</h1>
            <p class="text-muted mb-0 mt-1">@yield('page_intro', 'Unified bakery production, outlet restocking, and wholesale order management.')</p>
        </div>
        <div class="col-sm-4">
            <div class="float-sm-right text-sm text-muted text-sm-right mt-2 mt-sm-0">
                <div><strong>Date:</strong> {{ now()->format('d M Y') }}</div>
                @auth
                    <div><strong>User:</strong> {{ auth()->user()->name }}</div>
                @endauth
            </div>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <strong>Please check the form and try again.</strong>
            <ul class="mb-0 mt-2 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('page')
@stop

@section('footer')
    <div class="float-right d-none d-sm-inline">ZuriMart Unified Bakery Management System</div>
    <strong>Website:</strong> zurimartbakeryservices.com
@stop
