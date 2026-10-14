<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Shifr Shield - Managed MCP Data Gateway' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="page">
        <!-- Navbar -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                        <span class="badge bg-primary text-primary-fg p-2 me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-lock" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                <path d="M12 11m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M12 12l0 2.5" />
                            </svg>
                        </span>
                        <div>
                            <div class="fw-bold fs-2 text-dark">Shifr Shield</div>
                            <div class="text-secondary fs-6">Managed MCP Data Gateway</div>
                        </div>
                    </a>
                </div>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item d-none d-md-flex me-3">
                        <span class="badge bg-green-lt">
                            <span class="status-dot status-dot-animated bg-green me-1"></span> Gateway Engine Active (Port 3000)
                        </span>
                    </div>
                </div>
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <ul class="navbar-nav">
                        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('dashboard') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-dashboard" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 13m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                        <path d="M13.45 11.55l2.05 -2.05" />
                                        <path d="M6.4 20a9 9 0 1 1 11.2 0z" />
                                    </svg>
                                </span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('databases.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('databases.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-database" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" />
                                        <path d="M4 6v6a8 3 0 0 0 16 0v-6" />
                                        <path d="M4 12v6a8 3 0 0 0 16 0v-6" />
                                    </svg>
                                </span>
                                <span class="nav-link-title">Databases</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('api-keys.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('api-keys.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-key" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" />
                                        <path d="M15 9h.01" />
                                    </svg>
                                </span>
                                <span class="nav-link-title">API Keys & MCP Setup</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('audit-logs.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-analytics" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                        <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                        <path d="M9 17l0 -5" />
                                        <path d="M12 17l0 -1" />
                                        <path d="M15 17l0 -3" />
                                    </svg>
                                </span>
                                <span class="nav-link-title">Audit Logs</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <!-- Global Flash Messages -->
            <div class="container-xl pt-3">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 12l5 5l10 -10" />
                                </svg>
                            </div>
                            <div>{{ session('success') }}</div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 9v4" />
                                    <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                                    <path d="M12 16h.01" />
                                </svg>
                            </div>
                            <div>{{ session('error') }}</div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif
            </div>

            <!-- Page header -->
            @yield('header')

            <!-- Page body -->
            <div class="page-body">
                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">
                        <div class="col-lg-auto ms-lg-auto">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    <span class="badge bg-blue-lt">Alpha Internal v0.1</span>
                                </li>
                                <li class="list-inline-item">
                                    <span class="text-secondary">Security Protocol: AES-256 + MCP SSE</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    &copy; {{ date('Y') }} Shifr Shield. All rights reserved.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
