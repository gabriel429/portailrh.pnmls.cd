<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portail RH PNMLS')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/pnmls-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/pnmls-16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0077B5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PNMLS RH">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/pnmls-180.png') }}">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #0077B5;
            --primary-dark: #005a87;
            --primary-light: #e8f4fd;
            --secondary-color: #F5F5F5;
            --text-dark: #333;
            --text-light: #666;
            --border-color: #E5E5E5;
        }

        body {
            font-family: 'Segoe UI', 'Roboto', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-dark);
            padding-top: 0;
        }

        /* ──────────────────────────────────────
           NAVBAR – modern glassmorphism style
           ────────────────────────────────────── */
        .navbar-main {
            background: linear-gradient(135deg, #0077B5 0%, #005a87 50%, #004060 100%);
            box-shadow: 0 2px 20px rgba(0,0,0,.15);
            padding: .5rem 0;
            position: sticky;
            top: 0;
            z-index: 1050;
        }

        .navbar-main .navbar-brand {
            display: flex;
            align-items: center;
            gap: .6rem;
            color: #fff !important;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -.3px;
            text-decoration: none;
            padding: .25rem 0;
        }
        .navbar-main .navbar-brand img {
            height: 38px;
            width: 38px;
            object-fit: contain;
            border-radius: 8px;
            background: rgba(255,255,255,.15);
            padding: 3px;
        }
        .navbar-main .navbar-brand .brand-text {
            line-height: 1.15;
        }
        .navbar-main .navbar-brand .brand-title {
            font-size: 1rem;
            font-weight: 700;
        }
        .navbar-main .navbar-brand .brand-sub {
            font-size: .65rem;
            opacity: .7;
            font-weight: 400;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        /* Nav links */
        .navbar-main .nav-link {
            color: rgba(255,255,255,.8) !important;
            font-size: .835rem;
            font-weight: 500;
            padding: .5rem .85rem !important;
            border-radius: 8px;
            transition: all .2s ease;
            display: flex;
            align-items: center;
            gap: .4rem;
            margin: 0 1px;
            position: relative;
        }
        .navbar-main .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,.12);
        }
        .navbar-main .nav-link.active,
        .navbar-main .nav-item.active > .nav-link {
            color: #fff !important;
            background: rgba(255,255,255,.18);
        }
        .navbar-main .nav-link .nav-icon {
            width: 18px;
            text-align: center;
            font-size: .85rem;
            opacity: .85;
        }

        /* Notification badge on link */
        .navbar-main .nav-badge {
            position: absolute;
            top: 2px;
            right: 4px;
            background: #ef4444;
            color: #fff;
            font-size: .6rem;
            font-weight: 700;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        /* Divider between link groups */
        .nav-divider {
            width: 1px;
            height: 24px;
            background: rgba(255,255,255,.2);
            margin: auto 6px;
        }

        /* Dropdown menus */
        .navbar-main .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,.15);
            padding: .5rem;
            min-width: 220px;
            animation: navDropIn .2s ease;
        }
        @keyframes navDropIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .navbar-main .dropdown-item {
            font-size: .84rem;
            padding: .55rem .9rem;
            border-radius: 8px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: .5rem;
            transition: background .15s;
        }
        .navbar-main .dropdown-item:hover {
            background: var(--primary-light);
            color: var(--primary-color);
        }
        .navbar-main .dropdown-item .dd-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            flex-shrink: 0;
        }
        .navbar-main .dropdown-divider {
            margin: .35rem .5rem;
            border-color: var(--border-color);
        }

        /* Admin dropdown icons colors */
        .dd-icon-blue   { background: #dbeafe; color: #2563eb; }
        .dd-icon-green  { background: #dcfce7; color: #16a34a; }
        .dd-icon-purple { background: #ede9fe; color: #7c3aed; }
        .dd-icon-orange { background: #ffedd5; color: #ea580c; }
        .dd-icon-red    { background: #fee2e2; color: #dc2626; }
        .dd-icon-teal   { background: #ccfbf1; color: #0d9488; }
        .dd-icon-slate  { background: #e2e8f0; color: #475569; }

        /* User profile in navbar */
        .nav-user-btn {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: rgba(255,255,255,.9) !important;
            padding: .35rem .7rem .35rem .4rem !important;
            border-radius: 24px !important;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            transition: all .2s;
        }
        .nav-user-btn:hover {
            background: rgba(255,255,255,.2) !important;
        }
        .nav-user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .7rem;
            color: #fff;
            flex-shrink: 0;
        }
        .nav-user-name {
            font-size: .82rem;
            font-weight: 500;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* User dropdown extras */
        .user-dd-header {
            padding: .7rem .9rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: .35rem;
        }
        .user-dd-name {
            font-weight: 600;
            font-size: .9rem;
            color: var(--text-dark);
        }
        .user-dd-email {
            font-size: .75rem;
            color: var(--text-light);
        }

        /* Hamburger */
        .navbar-main .navbar-toggler {
            border: 1px solid rgba(255,255,255,.25);
            padding: .35rem .6rem;
            border-radius: 8px;
        }
        .navbar-main .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,0.85)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Mobile responsive */
        @media (max-width: 991.98px) {
            .navbar-main .navbar-collapse {
                background: rgba(0,60,90,.95);
                border-radius: 12px;
                padding: .75rem;
                margin-top: .5rem;
            }
            .navbar-main .nav-link {
                padding: .6rem .9rem !important;
                border-radius: 8px;
            }
            .nav-divider {
                width: 100%;
                height: 1px;
                margin: .35rem 0;
            }
            .nav-user-btn {
                justify-content: flex-start;
                border-radius: 8px !important;
                width: 100%;
            }
        }

        /* ──────────────────────────────────────
           GLOBAL STYLES (unchanged)
           ────────────────────────────────────── */

        /* Cards */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
            font-weight: 600;
        }

        /* Profile Header */
        .profile-header {
            background-color: white;
            padding: 40px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-color);
            object-fit: cover;
        }

        .profile-info h2 {
            margin: 0;
            color: var(--text-dark);
        }

        .profile-info .badge {
            background-color: var(--primary-color);
            margin-top: 10px;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #005a87;
            border-color: #005a87;
        }

        /* Tabs */
        .nav-tabs .nav-link {
            color: var(--text-light);
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background-color: transparent;
        }

        /* Tables */
        .table tbody tr:hover {
            background-color: var(--secondary-color);
        }

        .table thead th {
            background-color: var(--secondary-color);
            border-color: var(--border-color);
            font-weight: 600;
        }

        /* Alerts */
        .alert {
            border: none;
            border-left: 4px solid;
        }

        .alert-success {
            border-left-color: #28a745;
            background-color: #f0f9f5;
        }

        .alert-danger {
            border-left-color: #dc3545;
            background-color: #fdf5f5;
        }

        .alert-info {
            border-left-color: var(--primary-color);
            background-color: #f0f7ff;
        }

        /* Forms */
        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 119, 181, 0.25);
        }

        /* Sidebar */
        .sidebar {
            background-color: white;
            border-right: 1px solid var(--border-color);
            padding: 20px;
        }

        .sidebar .nav-link {
            color: var(--text-dark);
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .sidebar .nav-link:hover {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0077B5 0%, #004060 100%);
            padding: 20px;
            margin-top: 40px;
            text-align: center;
            color: rgba(255,255,255,.7);
            font-size: .82rem;
        }

        /* ── Notification bell ── */
        .nav-notif-btn {
            position: relative;
            padding: .4rem .5rem !important;
            font-size: 1.1rem;
            color: rgba(255,255,255,.8) !important;
            transition: color .2s;
        }
        .nav-notif-btn:hover { color: #fff !important; }
        .nav-notif-btn::after { display: none; }
        .notif-badge {
            position: absolute;
            top: 2px; right: 0;
            background: #ef4444;
            color: #fff;
            font-size: .6rem;
            font-weight: 800;
            padding: 1px 5px;
            border-radius: 10px;
            line-height: 1.3;
            min-width: 16px;
            text-align: center;
            border: 2px solid var(--primary-color);
            animation: notif-pulse 2s infinite;
        }
        @keyframes notif-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .notif-dropdown {
            width: 340px;
            padding: 0;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,.12);
            overflow: hidden;
        }
        .notif-dd-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .7rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }
        .notif-dd-title { font-weight: 700; font-size: .88rem; color: #1e293b; }
        .notif-dd-clear { font-size: .72rem; color: #0077B5; text-decoration: none; font-weight: 600; }
        .notif-dd-clear:hover { text-decoration: underline; }
        .notif-item {
            display: flex !important;
            align-items: center;
            gap: .6rem;
            padding: .6rem 1rem !important;
            border-bottom: 1px solid #f8fafc;
            white-space: normal !important;
        }
        .notif-item:hover { background: #f0f9ff !important; }
        .notif-unread { background: #f0f9ff; }
        .notif-item-icon {
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; flex-shrink: 0;
        }
        .notif-item-content { flex: 1; min-width: 0; }
        .notif-item-title {
            display: block; font-size: .78rem; font-weight: 600;
            color: #1e293b; line-height: 1.3;
        }
        .notif-item-time { font-size: .68rem; color: #9ca3af; }
        .notif-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #0077B5; flex-shrink: 0;
        }
        .notif-empty {
            text-align: center; padding: 1.5rem 1rem;
            color: #9ca3af; font-size: .82rem;
        }
        .notif-see-all {
            font-size: .8rem !important; font-weight: 600;
            color: #0077B5 !important; padding: .6rem 1rem !important;
        }
    </style>

    @yield('css')
</head>
<body>
    <!-- Navbar -->
    @if(auth()->check())
    @php
        $navUser = auth()->user();
        $navAgent = $navUser->agent;
        $navInitials = '';
        if ($navAgent) {
            $navInitials = mb_strtoupper(mb_substr($navAgent->prenom ?? '', 0, 1) . mb_substr($navAgent->nom ?? '', 0, 1));
        }
        if (!$navInitials) {
            $navInitials = mb_strtoupper(mb_substr($navUser->name ?? 'U', 0, 2));
        }
    @endphp
    <nav class="navbar navbar-expand-lg navbar-main">
        <div class="container-fluid px-3">
            {{-- Brand --}}
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo-pnmls.png') }}" alt="PNMLS">
                <div class="brand-text">
                    <div class="brand-title">Portail RH</div>
                    <div class="brand-sub">PNMLS</div>
                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                {{-- Centre / Main navigation --}}
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fas fa-th-large nav-icon"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}">
                            <i class="fas fa-id-badge nav-icon"></i> Mon Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('requests.*') ? 'active' : '' }}" href="{{ route('requests.index') }}">
                            <i class="fas fa-paper-plane nav-icon"></i> Demandes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                            <i class="fas fa-folder-open nav-icon"></i> Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('plan-travail.*') ? 'active' : '' }}" href="{{ route('plan-travail.index') }}">
                            <i class="fas fa-calendar-check nav-icon"></i> Plan de Travail
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('documents-travail.*') ? 'active' : '' }}" href="{{ route('documents-travail.index') }}">
                            <i class="fas fa-file-invoice nav-icon"></i> Docs Travail
                        </a>
                    </li>
                </ul>

                {{-- Right side --}}
                <ul class="navbar-nav align-items-lg-center">
                    @if($navUser->hasAdminAccess() || $navUser->isAdminNT())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-shield-halved nav-icon"></i> Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if($navUser->hasAdminAccess())
                            <li>
                                <a class="dropdown-item" href="{{ route('rh.agents.index') }}">
                                    <span class="dd-icon dd-icon-blue"><i class="fas fa-users"></i></span> Gestion Agents
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('rh.communiques.index') }}">
                                    <span class="dd-icon dd-icon-green"><i class="fas fa-bullhorn"></i></span> Communiqués
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('rh.pointages.index') }}">
                                    <span class="dd-icon dd-icon-purple"><i class="fas fa-clock"></i></span> Pointages
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('rh.affectations.index') }}">
                                    <span class="dd-icon dd-icon-orange"><i class="fas fa-exchange-alt"></i></span> Affectations
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('signalements.index') }}">
                                    <span class="dd-icon dd-icon-red"><i class="fas fa-flag"></i></span> Signalements
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('rh.dashboard') }}">
                                    <span class="dd-icon dd-icon-teal"><i class="fas fa-chart-line"></i></span> Tableau de Bord
                                </a>
                            </li>
                            @endif
                            @if($navUser->isAdminNT())
                            @if($navUser->hasAdminAccess())<li><hr class="dropdown-divider"></li>@endif
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                    <span class="dd-icon dd-icon-slate"><i class="fas fa-sliders-h"></i></span> Paramètres système
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    <div class="nav-divider d-none d-lg-block"></div>

                    {{-- Notification bell (loaded via AJAX) --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-notif-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="notifBell">
                            <i class="fas fa-bell"></i>
                            <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notif-dropdown" id="notifDropdown">
                            <li>
                                <div class="notif-dd-header">
                                    <span class="notif-dd-title">Notifications</span>
                                    <a href="#" class="notif-dd-clear" id="notifMarkAll" style="display:none;" onclick="event.preventDefault(); fetch('{{ url('/notifications/mark-all-read') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, credentials:'same-origin'}).then(()=>{ loadNotifs(); });">Tout marquer lu</a>
                                </div>
                            </li>
                            <li id="notifLoading"><div class="notif-empty"><i class="fas fa-spinner fa-spin"></i></div></li>
                            <li><hr class="dropdown-divider m-0"></li>
                            <li>
                                <a class="dropdown-item text-center notif-see-all" href="{{ url('/notifications') }}">
                                    Voir toutes les notifications
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- User dropdown --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-user-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <span class="nav-user-avatar">{{ $navInitials }}</span>
                            <span class="nav-user-name">{{ $navAgent?->prenom ?? $navUser->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="user-dd-header">
                                    <div class="user-dd-name">{{ $navAgent?->prenom ?? '' }} {{ $navAgent?->nom ?? $navUser->name }}</div>
                                    <div class="user-dd-email">{{ $navUser->email }}</div>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.show') }}">
                                    <span class="dd-icon dd-icon-blue"><i class="fas fa-user"></i></span> Mon Profil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">
                                        <span class="dd-icon dd-icon-red"><i class="fas fa-sign-out-alt"></i></span> Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @endif

    <!-- Container principal -->
    <div class="container-fluid">
        @if ($errors->any())
            <div class="alert alert-danger mt-3">
                <strong>Erreurs:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    <!-- Content -->
    @yield('content')

    <!-- Footer -->
    <footer class="footer mt-5">
        <p class="mb-0">&copy; 2026 Portail RH PNMLS — Programme National Multisectoriel de Lutte contre le Sida</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @yield('js')
    @stack('scripts')

    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(reg => {
            // Check for updates every 24 hours (not every 60s to avoid false positives)
            setInterval(() => reg.update(), 86400000);

            reg.addEventListener('updatefound', () => {
                const newWorker = reg.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        showUpdateBanner();
                    }
                });
            });
        });

        function showUpdateBanner() {
            if (document.getElementById('pwa-update-banner')) return;
            const banner = document.createElement('div');
            banner.id = 'pwa-update-banner';
            banner.innerHTML = `
                <div style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:99999;
                    background:linear-gradient(135deg,#0077B5,#005a87);color:#fff;padding:12px 20px;
                    border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.25);display:flex;align-items:center;
                    gap:12px;font-family:'Segoe UI',sans-serif;font-size:.88rem;max-width:420px;width:90%;">
                    <i class="fas fa-arrow-rotate-right" style="font-size:1.1rem;"></i>
                    <span style="flex:1;">Nouvelle version disponible !</span>
                    <button onclick="location.reload()" style="background:#fff;color:#0077B5;border:none;
                        padding:6px 16px;border-radius:8px;font-weight:700;font-size:.82rem;cursor:pointer;">
                        Mettre à jour
                    </button>
                    <button onclick="this.closest('#pwa-update-banner').remove()" style="background:none;
                        border:none;color:rgba(255,255,255,.7);font-size:1.2rem;cursor:pointer;padding:0 4px;">
                        &times;
                    </button>
                </div>`;
            document.body.appendChild(banner);
        }
    }

    // ── Notifications AJAX ──
    (function() {
        const badge = document.getElementById('notifBadge');
        const dropdown = document.getElementById('notifDropdown');
        const loading = document.getElementById('notifLoading');
        const markAll = document.getElementById('notifMarkAll');
        if (!badge) return;

        function loadNotifs() {
            fetch('/api/notifications/unread-count', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    // Update badge
                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = '';
                        markAll.style.display = '';
                    } else {
                        badge.style.display = 'none';
                        markAll.style.display = 'none';
                    }
                    // Update dropdown items
                    if (loading) loading.remove();
                    const divider = dropdown.querySelector('.dropdown-divider');
                    // Remove old notification items
                    dropdown.querySelectorAll('.notif-item-ajax').forEach(el => el.closest('li').remove());
                    if (data.recent && data.recent.length > 0) {
                        const escText = (s) => { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };
                        const escUrl = (s) => { try { const u = new URL(s, location.origin); return ['http:', 'https:'].includes(u.protocol) ? u.href : '#'; } catch { return '#'; } };
                        data.recent.forEach(n => {
                            const li = document.createElement('li');
                            li.innerHTML = `<a class="dropdown-item notif-item notif-item-ajax ${!n.lu ? 'notif-unread' : ''}" href="${escUrl(n.lien)}">
                                <span class="notif-item-icon" style="background:${escText(n.couleur)}20;color:${escText(n.couleur)};">
                                    <i class="fas ${escText(n.icone)}"></i>
                                </span>
                                <span class="notif-item-content">
                                    <span class="notif-item-title">${escText(n.titre)}</span>
                                    <span class="notif-item-time">${escText(n.temps)}</span>
                                </span>
                                ${!n.lu ? '<span class="notif-dot"></span>' : ''}
                            </a>`;
                            divider.parentElement.insertBefore(li, divider.parentElement);
                        });
                    } else {
                        const li = document.createElement('li');
                        li.innerHTML = '<div class="notif-empty notif-item-ajax">Aucune notification</div>';
                        divider.parentElement.insertBefore(li, divider.parentElement);
                    }
                })
                .catch(() => {});
        }

        // Load on page ready, then every 30 seconds
        loadNotifs();
        setInterval(loadNotifs, 30000);
    })();
    </script>
</body>
</html>
