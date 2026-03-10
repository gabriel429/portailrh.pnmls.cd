@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-edit me-2"></i> Modifier le Pointage</h2>
            <p class="text-muted mb-0">{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }} - {{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</p>
        </div>
        <a href="{{ route('rh.pointages.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Retour
        </a>
    </div>

    <!-- Formulaire -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreurs de validation:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('rh.pointages.update', $pointage) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Informations agent (lecture seule) -->
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-user me-2 text-primary"></i>Agent</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="agent_name" class="form-label">Nom de l'Agent</label>
                            <input type="text" class="form-control" id="agent_name"
                                value="{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="agent_matricule" class="form-label">Matricule</label>
                            <input type="text" class="form-control" id="agent_matricule"
                                value="{{ $pointage->agent->matricule_pnmls }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="date_pointage" class="form-label">Date de Pointage</label>
                            <input type="text" class="form-control" id="date_pointage"
                                value="{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }}" disabled>
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Heures de travail -->
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-clock me-2 text-primary"></i>Heures de Travail</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="heure_entree" class="form-label">Heure d'Entrée</label>
                            <input type="time" class="form-control @error('heure_entree') is-invalid @enderror"
                                id="heure_entree" name="heure_entree"
                                value="{{ old('heure_entree', $pointage->heure_entree) }}">
                            @error('heure_entree')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="heure_sortie" class="form-label">Heure de Sortie</label>
                            <input type="time" class="form-control @error('heure_sortie') is-invalid @enderror"
                                id="heure_sortie" name="heure_sortie"
                                value="{{ old('heure_sortie', $pointage->heure_sortie) }}">
                            @error('heure_sortie')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="heures_travaillees" class="form-label">Heures Travaillées</label>
                            <input type="number" step="0.5" class="form-control @error('heures_travaillees') is-invalid @enderror"
                                id="heures_travaillees" name="heures_travaillees"
                                value="{{ old('heures_travaillees', $pointage->heures_travaillees) }}"
                                placeholder="ex: 8.5">
                            @error('heures_travaillees')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Observations -->
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-sticky-note me-2 text-primary"></i>Observations</h5>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <textarea class="form-control @error('observations') is-invalid @enderror"
                                id="observations" name="observations" rows="4"
                                placeholder="Ajouter des observations...">{{ old('observations', $pointage->observations) }}</textarea>
                            @error('observations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Boutons d'action -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('rh.pointages.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const entreeInput = document.getElementById('heure_entree');
    const sortieInput = document.getElementById('heure_sortie');
    const heuresInput = document.getElementById('heures_travaillees');

    function calculateHeures() {
        if (entreeInput.value && sortieInput.value) {
            const entree = new Date(`2000-01-01 ${entreeInput.value}`);
            const sortie = new Date(`2000-01-01 ${sortieInput.value}`);
            const diff = (sortie - entree) / (1000 * 60 * 60);
            if (diff > 0) {
                heuresInput.value = diff.toFixed(1);
            }
        }
    }

    entreeInput.addEventListener('change', calculateHeures);
    sortieInput.addEventListener('change', calculateHeures);
});
</script>
@endsection
