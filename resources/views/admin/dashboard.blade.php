@extends('admin.layouts.sidebar')

@section('title', 'Tableau de bord – Paramètres')
@section('page-title', 'Tableau de bord – Paramètres système')

@section('content')
<div class="row g-3 mb-4">
    @php
    $cards = [
        ['label' => 'Provinces',    'value' => $stats['provinces'],   'icon' => 'fa-map-marked-alt', 'color' => '#0077B5', 'bg' => '#e8f4fd', 'route' => 'admin.provinces.index'],
        ['label' => 'Départements', 'value' => $stats['departments'], 'icon' => 'fa-building',       'color' => '#6366f1', 'bg' => '#ede9fe', 'route' => 'admin.departments.index'],
        ['label' => 'Sections',     'value' => $stats['sections'],    'icon' => 'fa-sitemap',        'color' => '#0ea5e9', 'bg' => '#e0f2fe', 'route' => 'admin.sections.index'],
        ['label' => 'Cellules',     'value' => $stats['cellules'],    'icon' => 'fa-cubes',          'color' => '#64748b', 'bg' => '#f1f5f9', 'route' => 'admin.cellules.index'],
        ['label' => 'Fonctions',    'value' => $stats['fonctions'],   'icon' => 'fa-briefcase',      'color' => '#7c3aed', 'bg' => '#ede9fe', 'route' => 'admin.fonctions.index'],
        ['label' => 'Localités SEL','value' => $stats['localites'] ?? 0, 'icon' => 'fa-map-pin',     'color' => '#0891b2', 'bg' => '#e0f7fa', 'route' => 'admin.localites.index'],
        ['label' => 'Grades',       'value' => $stats['grades'],      'icon' => 'fa-layer-group',    'color' => '#10b981', 'bg' => '#d1fae5', 'route' => 'admin.grades.index'],
        ['label' => 'Rôles',        'value' => $stats['roles'],       'icon' => 'fa-user-tag',       'color' => '#f59e0b', 'bg' => '#fef3c7', 'route' => 'admin.roles.index'],
        ['label' => 'Permissions',  'value' => $stats['permissions'], 'icon' => 'fa-key',            'color' => '#ef4444', 'bg' => '#fee2e2', 'route' => 'admin.roles.index'],
        ['label' => 'Agents total', 'value' => $stats['agents'],      'icon' => 'fa-users',          'color' => '#8b5cf6', 'bg' => '#ede9fe', 'route' => 'rh.agents.index'],
    ];
    @endphp

    @foreach($cards as $c)
    <div class="col-6 col-md-4 col-xl-3">
        <a href="{{ route($c['route']) }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-icon" style="background:{{ $c['bg'] }};color:{{ $c['color'] }}">
                    <i class="fas {{ $c['icon'] }}"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:{{ $c['color'] }}">{{ $c['value'] }}</div>
                    <div class="stat-label">{{ $c['label'] }}</div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="admin-table">
            <div class="p-3 border-bottom fw-600 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-map-marked-alt text-primary me-2"></i> Gestion des Provinces</span>
                <a href="{{ route('admin.provinces.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Ajouter
                </a>
            </div>
            <div class="p-3 text-muted" style="font-size:.9rem">
                Gérez les provinces, leurs secrétariats, gouverneurs et coordonnées officielles.
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="admin-table">
            <div class="p-3 border-bottom fw-600 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-layer-group text-success me-2"></i> Grades de l'État</span>
                <a href="{{ route('admin.grades.create') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i> Ajouter
                </a>
            </div>
            <div class="p-3 text-muted" style="font-size:.9rem">
                Gérez la hiérarchie des grades (Haut cadre, Collaboration, Exécution).
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="admin-table">
            <div class="p-3 border-bottom fw-600 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-tag text-warning me-2"></i> Rôles du système</span>
                <a href="{{ route('admin.roles.create') }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-plus me-1"></i> Ajouter
                </a>
            </div>
            <div class="p-3 text-muted" style="font-size:.9rem">
                Gérez les rôles d'accès des agents au portail RH.
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="admin-table">
            <div class="p-3 border-bottom fw-600 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-scroll text-danger me-2"></i> Journaux système</span>
                <a href="{{ route('admin.logs') }}" class="btn btn-sm btn-danger">
                    <i class="fas fa-eye me-1"></i> Voir
                </a>
            </div>
            <div class="p-3 text-muted" style="font-size:.9rem">
                Consultez les logs Laravel pour diagnostiquer les erreurs en production.
            </div>
        </div>
    </div>
</div>
@endsection
