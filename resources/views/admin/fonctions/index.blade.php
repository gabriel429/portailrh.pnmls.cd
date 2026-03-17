@extends('admin.layouts.sidebar')
@section('title', 'Fonctions')
@section('page-title', 'Gestion des Fonctions / Postes')

@section('topbar-actions')
<a href="{{ route('admin.fonctions.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle fonction
</a>
@endsection

@section('content')
@php
    $col = $fonctions->getCollection();
    $nbSEN  = $col->where('niveau_administratif', 'SEN')->count();
    $nbSEP  = $col->where('niveau_administratif', 'SEP')->count();
    $nbSEL  = $col->where('niveau_administratif', 'SEL')->count();
    $nbTOUS = $col->where('niveau_administratif', 'TOUS')->count();

    $typeStyles = [
        'direction'        => ['Direction',       '#1a2448', '#e2e8f0'],
        'service_rattache' => ['Service rattaché', '#7c3aed', '#ede9fe'],
        'departement'      => ['Département',     '#3b5de7', '#eef1fc'],
        'département'      => ['Département',     '#3b5de7', '#eef1fc'],
        'section'          => ['Section',          '#0891b2', '#e0f7fa'],
        'cellule'          => ['Cellule',          '#64748b', '#f1f5f9'],
        'appui'            => ['Appui',            '#d97706', '#fef3c7'],
        'province'         => ['Province (SEP)',   '#10b981', '#d1fae5'],
        'local'            => ['Local (SEL)',      '#0d9488', '#ccfbf1'],
    ];
@endphp

@include('admin.partials._index-header', [
    'icon'  => 'fa-briefcase',
    'title' => 'Fonctions / Postes',
    'desc'  => 'Postes et fonctions par niveau administratif',
    'color' => '#7c3aed',
    'bg'    => '#f3eeff',
    'stats' => [
        ['label' => 'Total', 'value' => $fonctions->total()],
        ['label' => 'SEN', 'value' => $nbSEN],
        ['label' => 'SEP', 'value' => $nbSEP],
        ['label' => 'SEL', 'value' => $nbSEL],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Intitulé</th>
                    <th>Niveau</th>
                    <th>Catégorie</th>
                    <th>Type</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($fonctions as $fonction)
                <tr>
                    <td class="fw-semibold">{{ $fonction->nom }}</td>
                    <td>
                        @php
                            $nivClass = match($fonction->niveau_administratif) {
                                'SEN' => 'badge-sen', 'SEP' => 'badge-sep',
                                'SEL' => 'badge-sel', default => 'badge-tous',
                            };
                        @endphp
                        <span class="badge {{ $nivClass }}">{{ $fonction->niveau_administratif }}</span>
                    </td>
                    <td>
                        @php
                            [$tLabel, $tColor, $tBg] = $typeStyles[$fonction->type_poste] ?? [$fonction->type_poste, '#64748b', '#f1f5f9'];
                        @endphp
                        <span class="badge" style="background:{{ $tBg }};color:{{ $tColor }};">{{ $tLabel }}</span>
                    </td>
                    <td>
                        @if($fonction->est_chef)
                            <span class="badge" style="background:#fef3c7;color:#d97706;"><i class="fas fa-crown me-1"></i>Responsable</span>
                        @else
                            <span class="badge" style="background:#f1f5f9;color:#64748b;">Collaborateur</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.fonctions.edit', $fonction) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.fonctions.destroy', $fonction) }}" method="POST" onsubmit="return confirm('Supprimer cette fonction ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-briefcase"></i></div>
                        <p>Aucune fonction enregistrée</p>
                        <a href="{{ route('admin.fonctions.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Ajouter</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($fonctions->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $fonctions->firstItem() }}–{{ $fonctions->lastItem() }} sur {{ $fonctions->total() }}</span>
        {{ $fonctions->links() }}
    </div>
    @endif
</div>
@endsection
