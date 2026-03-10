@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-file-text me-2"></i> Détails du Pointage</h2>
            <p class="text-muted mb-0">{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rh.pointages.edit', $pointage) }}" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i> Modifier
            </a>
            <a href="{{ route('rh.pointages.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Informations du pointage -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Informations du Pointage</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Date</label>
                            <p class="mb-0 fw-bold">{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Heure d'Entrée</label>
                            <p class="mb-0">
                                @if($pointage->heure_entree)
                                    <span class="badge bg-success">{{ $pointage->heure_entree }}</span>
                                @else
                                    <span class="badge bg-secondary">Non enregistrée</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Heure de Sortie</label>
                            <p class="mb-0">
                                @if($pointage->heure_sortie)
                                    <span class="badge bg-info">{{ $pointage->heure_sortie }}</span>
                                @else
                                    <span class="badge bg-secondary">Non enregistrée</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Heures Travaillées</label>
                            <p class="mb-0">
                                @if($pointage->heures_travaillees)
                                    <strong>{{ $pointage->heures_travaillees }} heures</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observations -->
            @if($pointage->observations)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2 text-primary"></i>Observations</h5>
                    </div>
                    <div class="card-body">
                        {{ $pointage->observations }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Informations agent -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-user me-2 text-primary"></i>Agent</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</strong>
                    </p>
                    <p class="mb-2">
                        <small class="text-muted">Matricule:</small><br>
                        <code>{{ $pointage->agent->matricule_pnmls }}</code>
                    </p>
                    <p class="mb-0">
                        <small class="text-muted">Email:</small><br>
                        <a href="mailto:{{ $pointage->agent->email }}">{{ $pointage->agent->email }}</a>
                    </p>
                </div>
            </div>

            <!-- Résumé -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Résumé</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">ID Pointage</small>
                        <p class="mb-0 fw-bold">#{{ $pointage->id }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Créé le</small>
                        <p class="mb-0">{{ $pointage->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Dernière modification</small>
                        <p class="mb-0">{{ $pointage->updated_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Actions</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('rh.pointages.destroy', $pointage) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-block btn-danger btn-sm w-100"
                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce pointage?');">
                            <i class="fas fa-trash me-2"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
