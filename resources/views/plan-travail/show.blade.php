@extends('layouts.app')

@section('title', 'Activite - ' . $activitePlan->titre)

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-calendar-check me-2"></i>{{ $activitePlan->titre }}</h1>
                    <p class="rh-sub">
                        Plan de Travail {{ $activitePlan->annee }}
                        @if($activitePlan->trimestre) | {{ $activitePlan->trimestre }} @endif
                        | {{ $activitePlan->niveau_administratif }}
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('plan-travail.index', ['annee' => $activitePlan->annee]) }}" class="btn-rh alt">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
                        @if($canEdit)
                        <a href="{{ route('plan-travail.edit', $activitePlan) }}" class="btn-rh main">
                            <i class="fas fa-edit me-1"></i> Modifier
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row mt-3 g-3">
            {{-- Colonne gauche: Detail --}}
            <div class="col-lg-7">
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-info-circle me-2 text-primary"></i>Details de l'activite</h3>
                        </div>
                        <div class="d-flex gap-2">
                            @php
                                $sBadge = match($activitePlan->statut) {
                                    'terminee' => 'bg-success',
                                    'en_cours' => 'bg-primary',
                                    default => 'bg-secondary',
                                };
                                $sLabel = match($activitePlan->statut) {
                                    'terminee' => 'Terminee',
                                    'en_cours' => 'En cours',
                                    default => 'Planifiee',
                                };
                            @endphp
                            <span class="badge {{ $sBadge }}">{{ $sLabel }}</span>
                        </div>
                    </header>
                    <div class="p-3">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Niveau</dt>
                            <dd class="col-sm-8">
                                {{ $activitePlan->niveau_administratif }}
                                @if($activitePlan->departement) - {{ $activitePlan->departement->nom }} @endif
                                @if($activitePlan->province) - {{ $activitePlan->province->nom }} @endif
                                @if($activitePlan->localite) - {{ $activitePlan->localite->nom }} @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Annee</dt>
                            <dd class="col-sm-8">{{ $activitePlan->annee }}</dd>

                            <dt class="col-sm-4 text-muted">Trimestre</dt>
                            <dd class="col-sm-8">
                                @php
                                    $triLabels = ['T1' => '1er Trimestre', 'T2' => '2e Trimestre', 'T3' => '3e Trimestre', 'T4' => '4e Trimestre'];
                                @endphp
                                {{ $triLabels[$activitePlan->trimestre] ?? 'Annuel' }}
                            </dd>

                            @if($activitePlan->date_debut || $activitePlan->date_fin)
                            <dt class="col-sm-4 text-muted">Periode</dt>
                            <dd class="col-sm-8">
                                {{ $activitePlan->date_debut?->format('d/m/Y') ?? '?' }}
                                &rarr;
                                {{ $activitePlan->date_fin?->format('d/m/Y') ?? '?' }}
                                @if($activitePlan->date_fin?->isPast() && $activitePlan->statut !== 'terminee')
                                    <span class="badge bg-danger ms-1">En retard</span>
                                @endif
                            </dd>
                            @endif

                            <dt class="col-sm-4 text-muted">Cree par</dt>
                            <dd class="col-sm-8">{{ $activitePlan->createur?->nom_complet ?? 'N/A' }}</dd>

                            <dt class="col-sm-4 text-muted">Date de creation</dt>
                            <dd class="col-sm-8">{{ $activitePlan->created_at->format('d/m/Y a H:i') }}</dd>

                            @if($activitePlan->updated_at->ne($activitePlan->created_at))
                            <dt class="col-sm-4 text-muted">Derniere MAJ</dt>
                            <dd class="col-sm-8">{{ $activitePlan->updated_at->format('d/m/Y a H:i') }}</dd>
                            @endif
                        </dl>

                        {{-- Barre de progression --}}
                        <div class="mt-3 p-3 rounded" style="background: #f0f4f8;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">Progression</span>
                                <span class="fw-bold {{ $activitePlan->pourcentage >= 100 ? 'text-success' : 'text-primary' }}">{{ $activitePlan->pourcentage }}%</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar {{ $activitePlan->pourcentage >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $activitePlan->pourcentage }}%"></div>
                            </div>
                        </div>

                        @if($activitePlan->description)
                        <hr>
                        <h6 class="fw-bold mb-2">Description</h6>
                        <div style="white-space: pre-wrap;">{{ $activitePlan->description }}</div>
                        @endif

                        @if($activitePlan->observations)
                        <hr>
                        <h6 class="fw-bold mb-2">Observations</h6>
                        <div style="white-space: pre-wrap;" class="text-muted">{{ $activitePlan->observations }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Colonne droite: Mise a jour rapide --}}
            <div class="col-lg-5">
                @if(($canUpdateStatut ?? false) && $activitePlan->statut !== 'terminee')
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-sync-alt me-2 text-warning"></i>Mise a jour rapide</h3>
                        </div>
                    </header>
                    <div class="p-3">
                        <form action="{{ route('plan-travail.update-statut', $activitePlan) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="update_statut" class="form-label fw-bold">Statut</label>
                                <select class="form-select" id="update_statut" name="statut" required>
                                    <option value="planifiee" {{ $activitePlan->statut === 'planifiee' ? 'selected' : '' }}>Planifiee</option>
                                    <option value="en_cours" {{ $activitePlan->statut === 'en_cours' ? 'selected' : '' }}>En cours</option>
                                    <option value="terminee">Terminee</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="update_pourcentage" class="form-label fw-bold">Progression (%)</label>
                                <input type="range" class="form-range" id="update_pourcentage" name="pourcentage" min="0" max="100" step="5" value="{{ $activitePlan->pourcentage }}" oninput="document.getElementById('pct_val').textContent = this.value + '%'">
                                <span id="pct_val" class="fw-bold text-primary">{{ $activitePlan->pourcentage }}%</span>
                            </div>

                            <div class="mb-3">
                                <label for="update_observations" class="form-label fw-bold">Observations</label>
                                <textarea class="form-control" id="update_observations" name="observations" rows="3">{{ $activitePlan->observations }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-save me-1"></i> Mettre a jour
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if($canEdit)
                <div class="dash-panel {{ $activitePlan->statut !== 'terminee' ? 'mt-3' : '' }}">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-cog me-2 text-secondary"></i>Actions</h3>
                        </div>
                    </header>
                    <div class="p-3">
                        <a href="{{ route('plan-travail.edit', $activitePlan) }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-edit me-1"></i> Modifier completement
                        </a>
                        <form action="{{ route('plan-travail.destroy', $activitePlan) }}" method="POST" onsubmit="return confirm('Supprimer cette activite ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-1"></i> Supprimer
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
