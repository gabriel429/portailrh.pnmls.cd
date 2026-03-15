@extends('layouts.app')

@section('title', 'Affectations - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-user-tie me-2"></i>Gestion des Affectations</h1>
                    <p class="rh-sub">Affectez les agents aux fonctions dans les structures administratives du PNMLS.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.affectations.create') }}" class="btn-rh main"><i class="fas fa-plus me-1"></i> Nouvelle affectation</a>
                    </div>
                </div>
            </div>
        </section>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($affectations->count() > 0)
            <div class="alert alert-info mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Total:</strong> {{ $affectations->total() }} affectation{{ $affectations->total() > 1 ? 's' : '' }}
            </div>

            @php
                $grouped = $affectations->getCollection()->groupBy('niveau_administratif');
                $organeStyles = [
                    'SEN' => ['label' => 'Secrétariat Exécutif National', 'color' => '#0d6efd', 'bg' => '#e8f0fe', 'icon' => 'fa-landmark'],
                    'SEP' => ['label' => 'Secrétariat Exécutif Provincial', 'color' => '#198754', 'bg' => '#e8f5e9', 'icon' => 'fa-map-marked-alt'],
                    'SEL' => ['label' => 'Secrétariat Exécutif Local', 'color' => '#0dcaf0', 'bg' => '#e0f7fa', 'icon' => 'fa-map-pin'],
                ];
            @endphp

            @foreach($organeStyles as $naKey => $style)
                @if(isset($grouped[$naKey]) && $grouped[$naKey]->count() > 0)
                <div class="card mb-4 border-0 shadow-sm" style="border-top: 4px solid {{ $style['color'] }};">
                    <div class="card-header" style="background-color: {{ $style['bg'] }}; border: none;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-0" style="color: {{ $style['color'] }};">
                                    <i class="fas {{ $style['icon'] }} me-2"></i>{{ $style['label'] }}
                                </h5>
                                <small class="text-muted">{{ $grouped[$naKey]->count() }} affectation{{ $grouped[$naKey]->count() > 1 ? 's' : '' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="rh-table-wrap">
                            <table class="rh-table">
                                <thead>
                                    <tr>
                                        <th>Agent</th>
                                        <th>Fonction</th>
                                        <th>Niveau</th>
                                        <th>Entité rattachée</th>
                                        <th>Début</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grouped[$naKey] as $aff)
                                    <tr>
                                        <td class="fw-semibold">{{ $aff->agent?->prenom }} {{ $aff->agent?->nom }}</td>
                                        <td>{{ $aff->fonction?->nom ?? '–' }}</td>
                                        <td>
                                            @php
                                                $niveauLabels = [
                                                    'direction' => 'Direction',
                                                    'service_rattache' => 'Service rattaché',
                                                    'département' => 'Département',
                                                    'section' => 'Section',
                                                    'cellule' => 'Cellule',
                                                    'province' => 'Province',
                                                    'local' => 'Local',
                                                ];
                                            @endphp
                                            <span class="rh-pill st-mid">{{ $niveauLabels[$aff->niveau] ?? ucfirst($aff->niveau ?? '–') }}</span>
                                        </td>
                                        <td>
                                            @if($aff->niveau_administratif === 'SEP')
                                                {{ $aff->province?->nom ?? '–' }}
                                            @elseif($aff->niveau_administratif === 'SEL')
                                                {{ $aff->localite?->nom ?? '–' }}
                                            @elseif($aff->niveau === 'cellule')
                                                {{ $aff->cellule?->nom ?? '–' }}
                                            @elseif(in_array($aff->niveau, ['section','service_rattache']))
                                                {{ $aff->section?->nom ?? '–' }}
                                            @elseif($aff->niveau === 'département')
                                                {{ $aff->department?->nom ?? '–' }}
                                            @else
                                                –
                                            @endif
                                        </td>
                                        <td>{{ $aff->date_debut ? \Carbon\Carbon::parse($aff->date_debut)->format('d/m/Y') : '–' }}</td>
                                        <td>
                                            @if($aff->actif)
                                                <span class="rh-pill st-ok">Actif</span>
                                            @else
                                                <span class="rh-pill st-neutral">Inactif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('rh.affectations.edit', $aff) }}" class="btn btn-outline-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('rh.affectations.destroy', $aff) }}" method="POST" style="display:inline;"
                                                      onsubmit="return confirm('Supprimer cette affectation ?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-outline-danger btn-sm" title="Supprimer"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach

            @if($affectations->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $affectations->links() }}
            </div>
            @endif
        @else
            <div class="rh-list-card text-center py-5">
                <i class="fas fa-user-tie fa-4x text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Aucune affectation</h5>
                <p class="text-muted">Il n'y a aucune affectation enregistrée.</p>
                <a href="{{ route('rh.affectations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nouvelle affectation
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
