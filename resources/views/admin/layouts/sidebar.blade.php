<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Paramètres') – Portail RH PNMLS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary:       #3b5de7;
            --primary-dark:  #2a4bcf;
            --sidebar-w:     260px;
            --topbar-h:      60px;
            --radius:        12px;
            --shadow-sm:     0 2px 8px rgba(0,0,0,.07);
            --shadow-md:     0 6px 24px rgba(0,0,0,.11);
            --border:        #e8ecf0;
            --text:          #374151;
            --text-muted:    #9ca3af;
            --bg-page:       #f0f3f8;
            --easing:        .22s ease;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            background: var(--bg-page);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            margin: 0;
            font-size: 14px;
        }

        /* ═══════════════════════════════════════ SIDEBAR */
        #admin-sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: linear-gradient(175deg, #1d2b55 0%, #0c1428 100%);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 28px rgba(0,0,0,.18);
            transition: left var(--easing);
        }

        /* Brand */
        .sb-brand {
            padding: 20px 18px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
        }
        .sb-logo {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), #0099e5);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(59,93,231,.5);
        }
        .sb-title   { color: #fff; font-size: .88rem; font-weight: 700; margin: 0; line-height: 1.2; }
        .sb-subtitle{ color: rgba(255,255,255,.38); font-size: .68rem; display: block; margin-top: 1px; }

        /* Nav */
        .sb-nav { flex: 1; overflow-y: auto; padding: 6px 0 10px; }
        .sb-nav::-webkit-scrollbar { width: 3px; }
        .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 2px; }

        .sb-section {
            font-size: .65rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.1px;
            color: rgba(255,255,255,.25);
            padding: 14px 18px 4px;
        }

        .sb-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 18px;
            color: rgba(255,255,255,.6);
            font-size: .855rem; font-weight: 400;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background var(--easing), color var(--easing), border-color var(--easing);
        }
        .sb-link:hover  { background: rgba(255,255,255,.07); color: #fff; border-left-color: rgba(255,255,255,.25); }
        .sb-link.active { background: rgba(59,93,231,.25); color: #fff; border-left-color: var(--primary); font-weight: 600; }
        .sb-link .ico   { width: 17px; text-align: center; font-size: .85rem; flex-shrink: 0; opacity: .85; }

        /* User footer */
        .sb-footer {
            padding: 12px 14px;
            border-top: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
        }
        .sb-user { display: flex; align-items: center; gap: 9px; margin-bottom: 10px; }
        .sb-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--primary), #0099e5);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .75rem; font-weight: 700; flex-shrink: 0;
        }
        .sb-uname { color: #fff; font-size: .82rem; font-weight: 600; line-height: 1.15; }
        .sb-urole { color: rgba(255,255,255,.38); font-size: .68rem; }

        /* ═══════════════════════════════════════ MAIN */
        #admin-main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex; flex-direction: column;
            transition: margin var(--easing);
        }

        /* ═══════════════════════════════════════ TOPBAR */
        #admin-topbar {
            height: var(--topbar-h);
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 22px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 1px 6px rgba(0,0,0,.05);
            flex-shrink: 0;
        }
        .tb-left { display: flex; align-items: center; gap: 12px; }
        .tb-toggle {
            width: 36px; height: 36px;
            border: none; background: var(--bg-page); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #6b7280; cursor: pointer;
            transition: background var(--easing);
        }
        .tb-toggle:hover { background: var(--border); color: var(--text); }

        .tb-breadcrumb {
            display: flex; align-items: center; gap: 7px;
            font-size: .82rem; color: var(--text-muted);
        }
        .tb-page { font-size: 1rem; font-weight: 700; color: #1a2448; }

        .tb-right { display: flex; align-items: center; gap: 6px; }
        .tb-icon {
            width: 36px; height: 36px;
            border: none; background: var(--bg-page); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #6b7280; cursor: pointer; text-decoration: none;
            transition: background var(--easing);
            font-size: .88rem;
        }
        .tb-icon:hover { background: var(--border); color: var(--text); }
        .tb-user {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 11px;
            background: var(--bg-page); border-radius: 8px;
        }
        .tb-av {
            width: 27px; height: 27px;
            background: linear-gradient(135deg, var(--primary), #0099e5);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .7rem; font-weight: 700;
        }
        .tb-uname { font-size: .84rem; font-weight: 600; color: var(--text); }

        /* ═══════════════════════════════════════ PAGE HEADER */
        .page-header {
            padding: 18px 24px 0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .page-header h1 { font-size: 1.25rem; font-weight: 700; color: #1a2448; margin: 0; }
        .page-header .breadcrumb { font-size: .78rem; color: var(--text-muted); margin: 3px 0 0; }
        .page-header .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .page-header .breadcrumb a:hover { color: var(--primary); }

        /* ═══════════════════════════════════════ CONTENT */
        .page-content { padding: 20px 24px 24px; flex: 1; }

        /* ═══════════════════════════════════════ STAT CARDS */
        .stat-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--easing), box-shadow var(--easing);
            text-decoration: none;
            display: flex; align-items: center; gap: 14px;
            position: relative; overflow: hidden;
            border-top: 3px solid var(--acc);
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-ico {
            width: 50px; height: 50px;
            border-radius: 11px;
            background: var(--acc-bg);
            color: var(--acc);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; flex-shrink: 0;
        }
        .stat-num { font-size: 1.85rem; font-weight: 800; color: var(--acc); line-height: 1; }
        .stat-lbl { font-size: .73rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); margin-top: 2px; }

        .stat-card-sm {
            background: #fff;
            border-radius: var(--radius);
            padding: 16px 14px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--easing), box-shadow var(--easing);
            text-decoration: none;
            display: block; text-align: center;
            border-top: 3px solid var(--acc);
        }
        .stat-card-sm:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-card-sm .stat-ico { margin: 0 auto 10px; }
        .stat-card-sm .stat-num { font-size: 1.5rem; }

        /* ═══════════════════════════════════════ PANELS / TABLES */
        .panel {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        .panel-header {
            padding: 14px 18px;
            border-bottom: 1px solid #f0f3f8;
            display: flex; align-items: center; justify-content: space-between;
            font-weight: 600; color: #1a2448; font-size: .88rem;
        }
        .panel-ico {
            width: 30px; height: 30px;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: .82rem; margin-right: 9px; flex-shrink: 0;
        }

        .admin-table { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
        .admin-table thead th {
            background: #f8fafc;
            font-weight: 600; font-size: .78rem;
            text-transform: uppercase; letter-spacing: .4px;
            color: #374151; border-bottom: 2px solid var(--border);
            padding: 10px 14px;
        }
        .admin-table tbody td { padding: 10px 14px; vertical-align: middle; }
        .admin-table tbody tr:hover { background: #f8fafc; }

        /* ═══════════════════════════════════════ QUICK ACTIONS */
        .qa-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--easing), box-shadow var(--easing);
            text-decoration: none; display: block;
            border-left: 4px solid var(--acc);
        }
        .qa-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .qa-ico {
            width: 40px; height: 40px;
            border-radius: 9px;
            background: var(--acc-bg); color: var(--acc);
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; margin-bottom: 12px;
        }
        .qa-title { font-weight: 700; color: #1a2448; font-size: .9rem; margin-bottom: 4px; }
        .qa-desc  { font-size: .78rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.45; }

        /* ═══════════════════════════════════════ FORM CARDS */
        .form-card { background: #fff; border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow-sm); }
        .form-label { font-size: .84rem; font-weight: 600; color: #374151; }
        .form-control, .form-select {
            border-radius: 8px; border: 1.5px solid var(--border);
            font-size: .88rem; padding: 9px 13px;
            transition: border-color var(--easing), box-shadow var(--easing);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,93,231,.12);
        }

        /* Buttons */
        .btn { border-radius: 8px; font-weight: 500; font-size: .855rem; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }

        /* ═══════════════════════════════════════ LOGS */
        #log-output {
            background: #0d1117; color: #c9d1d9;
            font-family: 'Courier New', monospace; font-size: .76rem;
            padding: 16px; border-radius: 10px;
            max-height: 600px; overflow-y: auto;
            white-space: pre-wrap; word-break: break-all;
        }
        .log-error   { color: #ff7b72; }
        .log-warning { color: #e3b341; }
        .log-info    { color: #58a6ff; }

        /* ═══════════════════════════════════════ ALERTS */
        .alert { border-radius: 10px; border: none; font-size: .86rem; }
        .alert-success { background: #f0fdf4; color: #166534; }
        .alert-danger  { background: #fef2f2; color: #991b1b; }

        /* ═══════════════════════════════════════ MOBILE */
        .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999; }
        @media (max-width: 768px) {
            #admin-sidebar { left: calc(-1 * var(--sidebar-w)); }
            #admin-sidebar.open { left: 0; }
            #admin-main { margin-left: 0 !important; }
            .sb-overlay.show { display: block; }
            .page-content { padding: 14px 16px; }
            .page-header { padding: 14px 16px 0; }
        }
    </style>

    @yield('css')
</head>
<body>

{{-- Mobile overlay --}}
<div class="sb-overlay" id="sb-overlay"></div>

{{-- ═══ SIDEBAR ═══ --}}
<nav id="admin-sidebar">

    <div class="sb-brand">
        <div class="sb-logo"><i class="fas fa-shield-alt"></i></div>
        <div>
            <p class="sb-title">Portail RH PNMLS</p>
            <span class="sb-subtitle">Administration NT</span>
        </div>
    </div>

    <div class="sb-nav">
        <p class="sb-section">Navigation</p>
        <a href="{{ route('admin.dashboard') }}"
           class="sb-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt ico"></i> Tableau de bord
        </a>
        <a href="{{ route('dashboard') }}" class="sb-link">
            <i class="fas fa-home ico"></i> Portail RH
        </a>

        <p class="sb-section">Structure organisationnelle</p>
        <a href="{{ route('admin.provinces.index') }}"
           class="sb-link {{ request()->routeIs('admin.provinces.*') ? 'active' : '' }}">
            <i class="fas fa-map-marked-alt ico"></i> Provinces
        </a>
        <a href="{{ route('admin.departments.index') }}"
           class="sb-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
            <i class="fas fa-building ico"></i> Départements
        </a>
        <a href="{{ route('admin.sections.index') }}"
           class="sb-link {{ request()->routeIs('admin.sections.*') ? 'active' : '' }}">
            <i class="fas fa-sitemap ico"></i> Sections
        </a>
        <a href="{{ route('admin.cellules.index') }}"
           class="sb-link {{ request()->routeIs('admin.cellules.*') ? 'active' : '' }}">
            <i class="fas fa-cubes ico"></i> Cellules
        </a>
        <a href="{{ route('admin.fonctions.index') }}"
           class="sb-link {{ request()->routeIs('admin.fonctions.*') ? 'active' : '' }}">
            <i class="fas fa-briefcase ico"></i> Fonctions
        </a>
        <a href="{{ route('admin.affectations.index') }}"
           class="sb-link {{ request()->routeIs('admin.affectations.*') ? 'active' : '' }}">
            <i class="fas fa-user-tie ico"></i> Affectations
        </a>
        <a href="{{ route('admin.localites.index') }}"
           class="sb-link {{ request()->routeIs('admin.localites.*') ? 'active' : '' }}">
            <i class="fas fa-map-pin ico"></i> Localités SEL
        </a>

        <p class="sb-section">Référentiels</p>
        <a href="{{ route('admin.grades.index') }}"
           class="sb-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">
            <i class="fas fa-layer-group ico"></i> Grades
        </a>
        <a href="{{ route('admin.organes.index') }}"
           class="sb-link {{ request()->routeIs('admin.organes.*') ? 'active' : '' }}">
            <i class="fas fa-sitemap ico"></i> Organes
        </a>
        <a href="{{ route('admin.roles.index') }}"
           class="sb-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
            <i class="fas fa-user-tag ico"></i> Rôles &amp; Permissions
        </a>

        <p class="sb-section">Système</p>
        <a href="{{ route('admin.deployment.index') }}"
           class="sb-link {{ request()->routeIs('admin.deployment.*') ? 'active' : '' }}">
            <i class="fas fa-rocket ico"></i> Déploiement
        </a>
        <a href="{{ route('admin.logs') }}"
           class="sb-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
            <i class="fas fa-scroll ico"></i> Journaux (Logs)
        </a>
        <a href="{{ route('rh.agents.index') }}" class="sb-link">
            <i class="fas fa-users ico"></i> Gestion Agents
        </a>
    </div>

    <div class="sb-footer">
        <div class="sb-user">
            <div class="sb-avatar">
                {{ strtoupper(substr(auth()->user()->prenom ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()->nom ?? '', 0, 1)) }}
            </div>
            <div>
                <div class="sb-uname">{{ auth()->user()->prenom }} {{ auth()->user()->nom }}</div>
                <div class="sb-urole">{{ auth()->user()->role?->nom_role ?? 'Admin' }}</div>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-outline-light w-100" style="border-radius:8px;font-size:.78rem;">
                <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
            </button>
        </form>
    </div>

</nav>

{{-- ═══ MAIN ═══ --}}
<div id="admin-main">

    {{-- Topbar --}}
    <div id="admin-topbar">
        <div class="tb-left">
            <button class="tb-toggle" id="sb-toggle" aria-label="Menu">
                <i class="fas fa-bars" style="font-size:.9rem"></i>
            </button>
            <div class="tb-breadcrumb">
                <span>Admin NT</span>
                <i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.5"></i>
                <span class="tb-page">@yield('page-title', 'Tableau de bord')</span>
            </div>
        </div>
        <div class="tb-right">
            <a href="{{ route('admin.logs') }}" class="tb-icon" title="Journaux">
                <i class="fas fa-scroll"></i>
            </a>
            <a href="{{ route('dashboard') }}" class="tb-icon" title="Portail RH">
                <i class="fas fa-home"></i>
            </a>
            <div class="tb-user">
                <div class="tb-av">
                    {{ strtoupper(substr(auth()->user()->prenom ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()->nom ?? '', 0, 1)) }}
                </div>
                <span class="tb-uname d-none d-md-inline">{{ auth()->user()->prenom }}</span>
            </div>
        </div>
    </div>

    {{-- Page header with actions --}}
    <div class="page-header">
        <div>
            <h1>@yield('page-title', 'Tableau de bord')</h1>
            <nav class="breadcrumb mb-0">
                <a href="{{ route('admin.dashboard') }}">Accueil</a>
                <span class="mx-1 opacity-50">/</span>
                <span>@yield('title', 'Paramètres')</span>
            </nav>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @yield('topbar-actions')
        </div>
    </div>

    {{-- Content --}}
    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

    <footer class="text-center py-3" style="font-size:.75rem;color:var(--text-muted);border-top:1px solid var(--border);background:#fff;">
        &copy; {{ date('Y') }} Portail RH PNMLS &mdash; Section des Nouvelles Technologies
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sbSidebar = document.getElementById('admin-sidebar');
    const sbOverlay = document.getElementById('sb-overlay');
    const sbToggle  = document.getElementById('sb-toggle');
    sbToggle.addEventListener('click', () => {
        sbSidebar.classList.toggle('open');
        sbOverlay.classList.toggle('show');
    });
    sbOverlay.addEventListener('click', () => {
        sbSidebar.classList.remove('open');
        sbOverlay.classList.remove('show');
    });
</script>
@yield('js')
</body>
</html>

    <style>
        :root {
            --primary: #0077B5;
            --sidebar-bg: #1a2448;
            --sidebar-hover: #243060;
            --sidebar-active: #0077B5;
            --sidebar-width: 260px;
        }

        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }

        /* ── Sidebar ── */
        #admin-sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        #admin-sidebar .sidebar-brand {
            padding: 20px 24px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        #admin-sidebar .sidebar-brand h5 {
            color: #fff;
            font-weight: 700;
            margin: 0;
            font-size: 1rem;
        }

        #admin-sidebar .sidebar-brand small {
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
        }

        #admin-sidebar .nav-section {
            padding: 14px 16px 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.35);
        }

        #admin-sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 10px 24px;
            border-radius: 0;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        #admin-sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        #admin-sidebar .nav-link.active {
            background: var(--sidebar-active);
            color: #fff;
            font-weight: 600;
        }

        #admin-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        /* ── Main content ── */
        #admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ── */
        #admin-topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        #admin-topbar .page-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #1a2448;
            margin: 0;
        }

        /* ── Cards ── */
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 22px 20px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-card .stat-icon {
            width: 50px; height: 50px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .stat-card .stat-label { font-size: 0.8rem; color: #6b7280; margin-top: 2px; }

        /* ── Tables ── */
        .admin-table { background: #fff; border-radius: 10px; border: 1px solid #e5e7eb; overflow: hidden; }
        .admin-table thead th { background: #f8fafc; font-weight: 600; font-size: 0.85rem; color: #374151; border-bottom: 2px solid #e5e7eb; }
        .admin-table tbody tr:hover { background: #f8fafc; }

        /* ── Logs ── */
        #log-output {
            background: #0d1117;
            color: #c9d1d9;
            font-family: 'Courier New', monospace;
            font-size: 0.78rem;
            padding: 16px;
            border-radius: 8px;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .log-error   { color: #ff7b72; }
        .log-warning { color: #e3b341; }
        .log-info    { color: #58a6ff; }
    </style>

    @yield('css')
</head>
<body>

{{-- Sidebar --}}
<nav id="admin-sidebar">
    <div class="sidebar-brand">
        <h5><i class="fas fa-shield-alt me-2 text-primary"></i> Admin NT</h5>
        <small>Paramètres système</small>
    </div>

    <div class="flex-grow-1 overflow-auto py-2">
        <div class="nav-section">Navigation</div>
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt"></i> Tableau de bord
        </a>
        <a href="{{ route('dashboard') }}" class="nav-link">
            <i class="fas fa-home"></i> Portail RH
        </a>

        <div class="nav-section mt-2">Tables de référence</div>
        <a href="{{ route('admin.provinces.index') }}"
           class="nav-link {{ request()->routeIs('admin.provinces.*') ? 'active' : '' }}">
            <i class="fas fa-map-marked-alt"></i> Provinces
        </a>
        <a href="{{ route('admin.departments.index') }}"
           class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
            <i class="fas fa-building"></i> Départements
        </a>
        <a href="{{ route('admin.sections.index') }}"
           class="nav-link {{ request()->routeIs('admin.sections.*') ? 'active' : '' }}">
            <i class="fas fa-sitemap"></i> Sections
        </a>
        <a href="{{ route('admin.cellules.index') }}"
           class="nav-link {{ request()->routeIs('admin.cellules.*') ? 'active' : '' }}">
            <i class="fas fa-cubes"></i> Cellules
        </a>
        <a href="{{ route('admin.fonctions.index') }}"
           class="nav-link {{ request()->routeIs('admin.fonctions.*') ? 'active' : '' }}">
            <i class="fas fa-briefcase"></i> Fonctions
        </a>
        <a href="{{ route('admin.affectations.index') }}"
           class="nav-link {{ request()->routeIs('admin.affectations.*') ? 'active' : '' }}">
            <i class="fas fa-user-tie"></i> Affectations
        </a>
        <a href="{{ route('admin.localites.index') }}"
           class="nav-link {{ request()->routeIs('admin.localites.*') ? 'active' : '' }}">
            <i class="fas fa-map-pin"></i> Localités SEL
        </a>
        <a href="{{ route('admin.grades.index') }}"
           class="nav-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">
            <i class="fas fa-layer-group"></i> Grades
        </a>
        <a href="{{ route('admin.roles.index') }}"
           class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
            <i class="fas fa-user-tag"></i> Rôles
        </a>

        <div class="nav-section mt-2">Système</div>
        <a href="{{ route('admin.deployment.index') }}"
           class="nav-link {{ request()->routeIs('admin.deployment.*') ? 'active' : '' }}">
            <i class="fas fa-rocket"></i> Déploiement
        </a>
        <a href="{{ route('admin.logs') }}"
           class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
            <i class="fas fa-scroll"></i> Journaux (Logs)
        </a>
        <a href="{{ route('rh.agents.index') }}" class="nav-link">
            <i class="fas fa-users"></i> Gestion Agents
        </a>
    </div>

    <div class="p-3 border-top" style="border-color: rgba(255,255,255,0.1)!important">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                 style="width:34px;height:34px;flex-shrink:0">
                <i class="fas fa-user text-white" style="font-size:.8rem"></i>
            </div>
            <div>
                <div class="text-white" style="font-size:.82rem;font-weight:600">
                    {{ auth()->user()->prenom }} {{ auth()->user()->nom }}
                </div>
                <div style="font-size:.7rem;color:rgba(255,255,255,.4)">
                    {{ auth()->user()->role?->nom_role }}
                </div>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-outline-light w-100">
                <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
            </button>
        </form>
    </div>
</nav>

{{-- Main --}}
<div id="admin-main">
    <div id="admin-topbar">
        <h1 class="page-title">@yield('page-title', 'Paramètres')</h1>
        <div class="d-flex gap-2">
            @yield('topbar-actions')
        </div>
    </div>

    <div class="p-4 flex-grow-1">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

    <footer class="text-center py-3 text-muted" style="font-size:.8rem;border-top:1px solid #e5e7eb">
        &copy; {{ date('Y') }} Portail RH PNMLS – Section Nouvelle Technologie
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@yield('js')
</body>
</html>
