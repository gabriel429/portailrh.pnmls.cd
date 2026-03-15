@extends('admin.layouts.sidebar')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('css')
<style>
    /* Welcome banner */
    .welcome-banner {
        background: linear-gradient(135deg, #1d2b55 0%, #3b5de7 60%, #0099e5 100%);
        border-radius: 16px;
        padding: 28px 32px;
        position: relative;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 200px; height: 200px;
        background: rgba(255,255,255,.06);
        border-radius: 50%;
    }
    .welcome-banner::after {
        content: '';
        position: absolute;
        bottom: -60px; right: 80px;
        width: 260px; height: 260px;
        background: rgba(255,255,255,.04);
        border-radius: 50%;
    }
    .welcome-banner h2 { color: #fff; font-size: 1.4rem; font-weight: 700; margin: 0 0 6px; }
    .welcome-banner p  { color: rgba(255,255,255,.65); margin: 0; font-size: .88rem; }
    .welcome-banner .wb-icon {
        position: absolute; right: 30px; top: 50%;
        transform: translateY(-50%);
        font-size: 5rem;
        color: rgba(255,255,255,.07);
    }

    /* Section title */
    .section-title {
        font-size: .7rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1px;
        color: #9ca3af; margin-bottom: 12px;
        display: flex; align-items: center; gap: 6px;
    }
    .section-title::before {
        content: '';
        display: inline-block;
        width: 3px; height: 14px;
        background: var(--primary);
        border-radius: 2px;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: .3; }
        100% { opacity: 1; }
    }
</style>
@endsection

@section('content')

{{-- Welcome Banner --}}
<div class="welcome-banner">
    <i class="fas fa-cog wb-icon"></i>
    <h2>Bienvenue, {{ auth()->user()->agent?->prenom ?? auth()->user()->name }} !</h2>
    <p>
        <i class="fas fa-calendar-alt me-1"></i>
        {{ now()->translatedFormat('l d F Y') }} &nbsp;&mdash;&nbsp;
        Portail RH PNMLS &ndash; Configuration du système
    </p>
</div>

{{-- ─── Utilisateurs connectés ─── --}}
@if(isset($connectedUsers) && $connectedUsers->count() > 0)
<div class="section-title"><span>Utilisateurs connectes ({{ $connectedUsers->count() }})</span></div>
<div class="panel mb-4">
    <div class="panel-header">
        <div class="d-flex align-items-center">
            <div class="panel-ico" style="background:#d1fae5;color:#10b981;">
                <i class="fas fa-circle" style="font-size:.5rem;animation:pulse 2s infinite;"></i>
            </div>
            <span>En ligne ces 30 dernières minutes</span>
        </div>
        <span class="badge" style="background:#d1fae5;color:#10b981;font-size:.8rem;padding:5px 12px;border-radius:20px;">
            {{ $connectedUsers->count() }} connecté{{ $connectedUsers->count() > 1 ? 's' : '' }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Role</th>
                    <th>Province</th>
                    <th>Derniere activite</th>
                    <th>Adresse IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($connectedUsers as $cu)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#3b5de7,#0099e5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700;flex-shrink:0;">
                                {{ strtoupper(substr($cu->agent?->prenom ?? 'U', 0, 1)) }}{{ strtoupper(substr($cu->agent?->nom ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.85rem;">{{ $cu->nom_complet }}</div>
                                <div style="font-size:.72rem;color:#9ca3af;">{{ $cu->agent?->id_agent ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge" style="background:#eef1fc;color:#3b5de7;font-size:.72rem;padding:4px 8px;border-radius:6px;">
                            {{ $cu->role }}
                        </span>
                    </td>
                    <td style="font-size:.85rem;">{{ $cu->province }}</td>
                    <td>
                        <div style="font-size:.82rem;font-weight:500;">{{ $cu->last_activity->format('H:i') }}</div>
                        <div style="font-size:.7rem;color:#9ca3af;">{{ $cu->last_activity->diffForHumans() }}</div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7280;">{{ $cu->ip_address ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ─── Statistiques principales ─── --}}
@php
$primary = [
    ['label' => 'Agents enregistrés', 'value' => $stats['agents'],      'icon' => 'fa-users',         'acc' => '#3b5de7', 'acc-bg' => '#eef1fc', 'route' => 'rh.agents.index'],
    ['label' => 'Provinces',          'value' => $stats['provinces'],   'icon' => 'fa-map-marked-alt','acc' => '#0ea5e9', 'acc-bg' => '#e0f2fe', 'route' => 'admin.provinces.index'],
    ['label' => 'Départements',       'value' => $stats['departments'], 'icon' => 'fa-building',      'acc' => '#6366f1', 'acc-bg' => '#ede9fe', 'route' => 'admin.departments.index'],
    ['label' => 'Fonctions / Postes', 'value' => $stats['fonctions'],   'icon' => 'fa-briefcase',     'acc' => '#7c3aed', 'acc-bg' => '#f3eeff', 'route' => 'admin.fonctions.index'],
];
$secondary = [
    ['label' => 'Sections',     'value' => $stats['sections'],          'icon' => 'fa-sitemap',    'acc' => '#0891b2', 'acc-bg' => '#e0f7fa', 'route' => 'admin.sections.index'],
    ['label' => 'Cellules',     'value' => $stats['cellules'],          'icon' => 'fa-cubes',      'acc' => '#64748b', 'acc-bg' => '#f1f5f9', 'route' => 'admin.cellules.index'],
    ['label' => 'Localités SEL','value' => $stats['localites'] ?? 0,   'icon' => 'fa-map-pin',    'acc' => '#0d9488', 'acc-bg' => '#ccfbf1', 'route' => 'admin.localites.index'],
    ['label' => 'Grades',       'value' => $stats['grades'],            'icon' => 'fa-layer-group','acc' => '#10b981', 'acc-bg' => '#d1fae5', 'route' => 'admin.grades.index'],
    ['label' => 'Rôles',        'value' => $stats['roles'],             'icon' => 'fa-user-tag',   'acc' => '#f59e0b', 'acc-bg' => '#fef3c7', 'route' => 'admin.roles.index'],
    ['label' => 'Permissions',  'value' => $stats['permissions'],       'icon' => 'fa-key',        'acc' => '#ef4444', 'acc-bg' => '#fee2e2', 'route' => 'admin.roles.index'],
    ['label' => 'Utilisateurs', 'value' => $stats['users'],             'icon' => 'fa-users-cog',  'acc' => '#ec4899', 'acc-bg' => '#fce7f3', 'route' => 'admin.utilisateurs.index'],
];
@endphp

<div class="section-title"><span>Statistiques principales</span></div>
<div class="row g-3 mb-4">
    @foreach($primary as $s)
    <div class="col-6 col-xl-3">
        <a href="{{ route($s['route']) }}" class="stat-card"
           style="--acc:{{ $s['acc'] }};--acc-bg:{{ $s['acc-bg'] }}">
            <div class="stat-ico"><i class="fas {{ $s['icon'] }}"></i></div>
            <div>
                <div class="stat-num">{{ $s['value'] }}</div>
                <div class="stat-lbl">{{ $s['label'] }}</div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- ─── Statistiques par Organes ─── --}}
@if(count($statsByOrgane) > 0)
<div class="section-title"><span>Gestion par Secrétariats Exécutifs</span></div>
<div class="row g-4 mb-4">
    @foreach($statsByOrgane as $organe)
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-top: 4px solid {{ $organe['color'] }};">
            <div class="card-header" style="background-color: {{ $organe['bg-color'] }}; border: none;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title mb-0" style="color: {{ $organe['color'] }};">
                            <i class="fas {{ $organe['icon'] }} me-2"></i>{{ $organe['nom'] }}
                        </h6>
                        <small class="text-muted">{{ $organe['sigle'] }}</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center p-2" style="background-color: {{ $organe['bg-color'] }}; border-radius: 8px;">
                            <div class="h5 mb-0" style="color: {{ $organe['color'] }};">{{ $organe['agents'] }}</div>
                            <small class="text-muted">Agents</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2" style="background-color: {{ $organe['bg-color'] }}; border-radius: 8px;">
                            <div class="h5 mb-0" style="color: {{ $organe['color'] }};">{{ $organe['fonctions'] }}</div>
                            <small class="text-muted">Fonctions</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-center p-2" style="background-color: {{ $organe['bg-color'] }}; border-radius: 8px;">
                            <div class="h5 mb-0" style="color: {{ $organe['color'] }};">{{ $organe['affectations'] }}</div>
                            <small class="text-muted">Affectations</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="section-title"><span>Données de référence</span></div>
<div class="row g-3 mb-4">
    @foreach($secondary as $s)
    <div class="col-6 col-md-4 col-xl-2">
        <a href="{{ route($s['route']) }}" class="stat-card-sm"
           style="--acc:{{ $s['acc'] }};--acc-bg:{{ $s['acc-bg'] }}">
            <div class="stat-ico"><i class="fas {{ $s['icon'] }}"></i></div>
            <div class="stat-num">{{ $s['value'] }}</div>
            <div class="stat-lbl">{{ $s['label'] }}</div>
        </a>
    </div>
    @endforeach
</div>

{{-- ─── Accès rapides ─── --}}
@php
$quickActions = [
    [
        'title'  => 'Gestion des Provinces',
        'desc'   => 'Ajouter, modifier ou supprimer des provinces et secrétariats provinciaux.',
        'icon'   => 'fa-map-marked-alt',
        'acc'    => '#0ea5e9', 'acc-bg' => '#e0f2fe',
        'create' => 'admin.provinces.create',
        'list'   => 'admin.provinces.index',
    ],[
        'title'  => 'Grades de la Fonction Publique',
        'desc'   => 'Hiérarchie des grades : Haut cadre, Collaboration et Exécution.',
        'icon'   => 'fa-layer-group',
        'acc'    => '#10b981', 'acc-bg' => '#d1fae5',
        'create' => 'admin.grades.create',
        'list'   => 'admin.grades.index',
    ],[
        'title'  => 'Rôles & Permissions',
        'desc'   => 'Gérez les niveaux d\'accès des agents au portail RH PNMLS.',
        'icon'   => 'fa-user-tag',
        'acc'    => '#f59e0b', 'acc-bg' => '#fef3c7',
        'create' => 'admin.roles.create',
        'list'   => 'admin.roles.index',
    ],[
        'title'  => 'Fonctions / Postes',
        'desc'   => '35 fonctions organisées par niveaux SEN, SEP et SEL.',
        'icon'   => 'fa-briefcase',
        'acc'    => '#7c3aed', 'acc-bg' => '#f3eeff',
        'create' => 'admin.fonctions.create',
        'list'   => 'admin.fonctions.index',
    ],[
        'title'  => 'Affectations',
        'desc'   => 'Affecter des agents à leurs fonctions dans les structures administratives.',
        'icon'   => 'fa-user-tie',
        'acc'    => '#3b5de7', 'acc-bg' => '#eef1fc',
        'create' => 'rh.affectations.create',
        'list'   => 'rh.affectations.index',
    ],[
        'title'  => 'Journaux système',
        'desc'   => 'Diagnostiquer les erreurs et surveiller l\'activité du serveur.',
        'icon'   => 'fa-scroll',
        'acc'    => '#ef4444', 'acc-bg' => '#fee2e2',
        'create' => null,
        'list'   => 'admin.logs',
    ],
];
@endphp

<div class="section-title"><span>Accès rapides</span></div>
<div class="row g-3">
    @foreach($quickActions as $qa)
    <div class="col-md-6 col-xl-4">
        <div class="qa-card" style="--acc:{{ $qa['acc'] }};--acc-bg:{{ $qa['acc-bg'] }}">
            <div class="qa-ico"><i class="fas {{ $qa['icon'] }}"></i></div>
            <div class="qa-title">{{ $qa['title'] }}</div>
            <div class="qa-desc">{{ $qa['desc'] }}</div>
            <div class="d-flex gap-2">
                <a href="{{ route($qa['list']) }}"
                   class="btn btn-sm"
                   style="background:{{ $qa['acc-bg'] }};color:{{ $qa['acc'] }};border:none;font-weight:600;font-size:.78rem;">
                    <i class="fas fa-list me-1"></i> Voir tout
                </a>
                @if($qa['create'])
                <a href="{{ route($qa['create']) }}"
                   class="btn btn-sm text-white"
                   style="background:{{ $qa['acc'] }};border:none;font-weight:600;font-size:.78rem;">
                    <i class="fas fa-plus me-1"></i> Ajouter
                </a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
