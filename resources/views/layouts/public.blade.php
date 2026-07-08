<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZuriMart Bakery')</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <style>
        .btn-default,
        .btn-light {
            color: #fff !important;
            background-color: #111 !important;
            border-color: #111 !important;
        }

        .btn-default:hover,
        .btn-default:focus,
        .btn-default:active,
        .btn-default:not(:disabled):not(.disabled).active,
        .btn-light:hover,
        .btn-light:focus,
        .btn-light:active,
        .btn-light:not(:disabled):not(.disabled).active {
            color: #fff !important;
            background-color: #000 !important;
            border-color: #000 !important;
        }

        .btn-default.disabled,
        .btn-default:disabled,
        .btn-light.disabled,
        .btn-light:disabled {
            color: #fff !important;
            background-color: #111 !important;
            border-color: #111 !important;
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <div class="content-wrapper ml-0" style="min-height: 100vh;">
            <div class="content-header">
                <div class="container">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <h1 class="m-0">@yield('page_title', 'ZuriMart Bakery')</h1>
                            <p class="text-muted mb-0 mt-1">@yield('page_intro', 'Place a bakery order without needing to sign in.')</p>
                        </div>
                        <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                            <div class="text-muted"><strong>Date:</strong> {{ now()->format('d M Y') }}</div>
                            <div class="text-muted"><strong>Website:</strong> zurimartbakeryservices.com</div>
                            <div class="mt-2">
                                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-warning">Admin Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container">
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
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
</body>
</html>
