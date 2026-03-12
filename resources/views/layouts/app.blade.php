<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portail RH PNMLS')</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #0077B5;
            --secondary-color: #F5F5F5;
            --text-dark: #333;
            --text-light: #666;
            --border-color: #E5E5E5;
        }

        body {
            font-family: 'Roboto', 'Segoe UI', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-dark);
        }

        /* Navbar */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--text-dark) !important;
            margin: 0 10px;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

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
            background-color: white;
            border-top: 1px solid var(--border-color);
            padding: 20px;
            margin-top: 40px;
            text-align: center;
            color: var(--text-light);
        }
    </style>

    @yield('css')
</head>
<body>
    <!-- Navbar -->
    @if(auth()->check())
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo-pnmls.png') }}" alt="PNMLS Logo" style="height: 50px; width: auto; object-fit: contain;" class="me-2">
                <span>Portail RH PNMLS</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="fas fa-home"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('profile.show', auth()->user()) }}">
                            <i class="fas fa-user-circle"></i> Mon Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('requests.index') }}">
                            <i class="fas fa-file-alt"></i> Mes Demandes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('documents.index') }}">
                            <i class="fas fa-folder"></i> Documents
                        </a>
                    </li>

                    @if(auth()->user()->hasAdminAccess())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('rh.agents.index') }}">Gestion Agents</a></li>
                            <li><a class="dropdown-item" href="{{ route('rh.pointages.index') }}">Pointages</a></li>
                            <li><a class="dropdown-item" href="{{ route('signalements.index') }}">Signalements</a></li>
                            <li><a class="dropdown-item" href="{{ route('rh.dashboard') }}">Tableau de Bord</a></li>
                            @if(auth()->user()->isAdminNT())
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                    <i class="fas fa-sliders-h me-1"></i> Paramètres système
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> {{ auth()->user()->prenom }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('profile.show', auth()->user()) }}">Mon Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Déconnexion</button>
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
        <p>&copy; 2026 Portail RH PNMLS - Programme National Multisectoriel de Lutte contre le Sida</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @yield('js')
</body>
</html>
