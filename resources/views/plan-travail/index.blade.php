@extends('layouts.app')

@section('title', 'Plan de Travail Annuel ' . $annee)

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-calendar-alt me-2"></i>Plan de Travail Annuel {{ $annee }}</h1>
                    <p class="rh-sub">Activites planifiees, en cours et terminees de votre unite organisationnelle.</p>
                </div>
                @if($canEdit)
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('plan-travail.create') }}" class="btn-rh main">
                            <i class="fas fa-plus-circle me-1"></i> Nouvelle activite
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Filtres --}}
        <div class="dash-panel mt-3">
            <div class="p-3">
                <form method="GET" action="{{ route('plan-travail.index') }}" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label mb-1 small fw-bold">Annee</label>
                        <select name="annee" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($y = now()->year + 1; $y >= 2023; $y--)
                                <option value="{{ $y }}" {{ $annee == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-1 small fw-bold">Trimestre</label>
                        <select name="trimestre" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Tous</option>
                            <option value="T1" {{ $trimestre === 'T1' ? 'selected' : '' }}>T1 (Jan-Mar)</option>
                            <option value="T2" {{ $trimestre === 'T2' ? 'selected' : '' }}>T2 (Avr-Jun)</option>
                            <option value="T3" {{ $trimestre === 'T3' ? 'selected' : '' }}>T3 (Jul-Sep)</option>
                            <option value="T4" {{ $trimestre === 'T4' ? 'selected' : '' }}>T4 (Oct-Dec)</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-1 small fw-bold">Statut</label>
                        <select name="statut" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Tous</option>
                            <option value="planifiee" {{ $statut === 'planifiee' ? 'selected' : '' }}>Planifiee</option>
                            <option value="en_cours" {{ $statut === 'en_cours' ? 'selected' : '' }}>En cours</option>
                            <option value="terminee" {{ $statut === 'terminee' ? 'selected' : '' }}>Terminee</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('plan-travail.index', ['annee' => $annee]) }}" class="btn btn-sm btn-outline-secondary">Reinitialiser</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- KPI resume --}}
        <div class="row g-3 mt-2">
            <div class="col-6 col-md-3">
                <div class="p-3 rounded text-center" style="background: #f0f4f8;">
                    <h3 class="mb-0 fw-bold">{{ $totalCount }}</h3>
                    <small class="text-muted">Total</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded text-center" style="background: #fff3e0;">
                    <h3 class="mb-0 fw-bold text-secondary">{{ $planifieeCount }}</h3>
                    <small class="text-muted">Planifiees</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded text-center" style="background: #e3f2fd;">
                    <h3 class="mb-0 fw-bold text-primary">{{ $enCoursCount }}</h3>
                    <small class="text-muted">En cours</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded text-center" style="background: #e8f5e9;">
                    <h3 class="mb-0 fw-bold text-success">{{ $termineeCount }}</h3>
                    <small class="text-muted">Terminees</small>
                </div>
            </div>
        </div>

        {{-- Barre de progression globale --}}
        @if($totalCount > 0)
        <div class="mt-3 p-3 bg-white rounded shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="fw-bold">Progression globale</small>
                <small class="text-muted">{{ $avgPourcentage }}%</small>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-success" style="width: {{ $avgPourcentage }}%"></div>
            </div>
        </div>
        @endif

        {{-- Activites groupees par trimestre --}}
        @forelse($activitesGroupees as $tri => $groupeActivites)
            <div class="dash-panel mt-3">
                <header class="panel-head">
                    <div>
                        @php
                            $triLabels = [
                                'T1' => '1er Trimestre (Janvier - Mars)',
                                'T2' => '2e Trimestre (Avril - Juin)',
                                'T3' => '3e Trimestre (Juillet - Septembre)',
                                'T4' => '4e Trimestre (Octobre - Decembre)',
                                'Annuel' => 'Activites annuelles',
                            ];
                        @endphp
                        <h3 class="panel-title">
                            <i class="fas fa-layer-group me-2 text-info"></i>{{ $triLabels[$tri] ?? $tri }}
                            <span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">{{ $groupeActivites->count() }} activite{{ $groupeActivites->count() > 1 ? 's' : '' }}</span>
                        </h3>
                    </div>
                </header>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>Activite</th>
                                <th style="width: 100px;">Statut</th>
                                <th style="width: 150px;">Periode</th>
                                <th style="width: 120px;">Progression</th>
                                <th style="width: 80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupeActivites as $activite)
                                <tr>
                                    <td>
                                        <a href="{{ route('plan-travail.show', $activite) }}" class="text-decoration-none">
                                            <strong class="text-dark">{{ $activite->titre }}</strong>
                                        </a>
                                        @if($activite->description)
                                            <br><small class="text-muted">{{ Str::limit($activite->description, 80) }}</small>
                                        @endif
                                        <br><small class="text-muted">
                                            <i class="fas fa-building me-1"></i>
                                            {{ $activite->niveau_administratif }}
                                            @if($activite->departement) - {{ $activite->departement->nom }} @endif
                                            @if($activite->province) - {{ $activite->province->nom }} @endif
                                        </small>
                                    </td>
                                    <td>
                                        @php
                                            $sBadge = match($activite->statut) {
                                                'terminee' => 'bg-success',
                                                'en_cours' => 'bg-primary',
                                                default => 'bg-secondary',
                                            };
                                            $sLabel = match($activite->statut) {
                                                'terminee' => 'Terminee',
                                                'en_cours' => 'En cours',
                                                default => 'Planifiee',
                                            };
                                        @endphp
                                        <span class="badge {{ $sBadge }}">{{ $sLabel }}</span>
                                    </td>
                                    <td>
                                        <small>
                                            @if($activite->date_debut)
                                                {{ $activite->date_debut->format('d/m') }}
                                            @endif
                                            @if($activite->date_fin)
                                                &rarr; {{ $activite->date_fin->format('d/m') }}
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar {{ $activite->pourcentage >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $activite->pourcentage }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $activite->pourcentage }}%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('plan-travail.show', $activite) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($canEdit)
                                        <a href="{{ route('plan-travail.edit', $activite) }}" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="dash-panel mt-3">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-calendar-alt fa-3x mb-3 d-block"></i>
                    <p class="mb-0">Aucune activite pour l'annee {{ $annee }}.</p>
                    @if($canEdit)
                        <a href="{{ route('plan-travail.create') }}" class="btn btn-sm btn-outline-primary mt-3">
                            <i class="fas fa-plus-circle me-1"></i> Creer la premiere activite
                        </a>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
