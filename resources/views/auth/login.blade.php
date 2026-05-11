@extends('adminlte::auth.login')

@section('auth_header', 'ZuriMart Bakery Admin Login')

@section('auth_body')
    <form action="{{ route('login.store') }}" method="post">
        @csrf

        <div class="input-group mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}" placeholder="Email" autofocus>
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope"></span></div>
            </div>
            @error('email')
                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                placeholder="Password">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-lock"></span></div>
            </div>
            @error('password')
                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="row">
            <div class="col-7">
                <div class="icheck-primary">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Remember me</label>
                </div>
            </div>
            <div class="col-5">
                <button type="submit" class="btn btn-warning btn-block">Sign In</button>
            </div>
        </div>
    </form>
@stop

@section('auth_footer')
    <p class="mb-1 text-muted">Use `info@zurimartbakeryservices.com` and password `password`.</p>
    <p class="mb-0">
        <a href="{{ route('orders.create') }}">Continue to the public order form</a>
    </p>
@stop
