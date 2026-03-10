@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-clock-o me-2"></i> Ajouter un Pointage</h2>
            <p class="text-muted mb-0">Enregistrez un nouveau pointage</p>
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

            <form action="{{ route('rh.pointages.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="agent_id" class="form-label">Agent <span class="text-danger">*</span></label>
                        <select class="form-select @error('agent_id') is-invalid @enderror"
                            id="agent_id" name="agent_id" required>
                            <option value="">-- Sélectionner un agent --</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" @selected(old('agent_id') == $agent->id)>
                                    {{ $agent->matricule_pnmls }} - {{ $agent->prenom }} {{ $agent->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('agent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="date_pointage" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_pointage') is-invalid @enderror"
                            id="date_pointage" name="date_pointage" value="{{ old('date_pointage') }}" required>
                        @error('date_pointage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="heure_entree" class="form-label">Heure d'Entrée</label>
                        <input type="time" class="form-control @error('heure_entree') is-invalid @enderror"
                            id="heure_entree" name="heure_entree" value="{{ old('heure_entree') }}">
                        @error('heure_entree')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="heure_sortie" class="form-label">Heure de Sortie</label>
                        <input type="time" class="form-control @error('heure_sortie') is-invalid @enderror"
                            id="heure_sortie" name="heure_sortie" value="{{ old('heure_sortie') }}">
                        @error('heure_sortie')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="heures_travaillees" class="form-label">Heures Travaillées</label>
                        <input type="number" step="0.5" class="form-control @error('heures_travaillees') is-invalid @enderror"
                            id="heures_travaillees" name="heures_travaillees" value="{{ old('heures_travaillees') }}" placeholder="ex: 8.5">
                        @error('heures_travaillees')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="observations" class="form-label">Observations</label>
                        <textarea class="form-control @error('observations') is-invalid @enderror"
                            id="observations" name="observations" rows="3" placeholder="Ajouter des observations...">{{ old('observations') }}</textarea>
                        @error('observations')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <!-- Boutons d'action -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Créer le pointage
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
