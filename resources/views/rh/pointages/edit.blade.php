@extends('layouts.app')

@section('title', 'Modifier Pointage - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-edit me-2"></i>Modifier le pointage</h1>
                    <p class="rh-sub">{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }} - {{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.pointages.show', $pointage) }}" class="btn-rh alt"><i class="fas fa-arrow-left me-1"></i> Retour details</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="rh-list-card p-3 p-lg-4">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Erreurs de validation</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('rh.pointages.update', $pointage) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">Agent</label>
                    <input type="text" class="form-control" value="{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ID</label>
                    <input type="text" class="form-control" value="{{ $pointage->agent->id_agent }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date pointage</label>
                    <input type="text" class="form-control" value="{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }}" disabled>
                </div>

                <div class="col-md-6">
                    <label for="heure_entree" class="form-label">Heure d'entree</label>
                    <input type="time" class="form-control @error('heure_entree') is-invalid @enderror" id="heure_entree" name="heure_entree" value="{{ old('heure_entree', $pointage->heure_entree) }}">
                    @error('heure_entree')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="heure_sortie" class="form-label">Heure de sortie</label>
                    <input type="time" class="form-control @error('heure_sortie') is-invalid @enderror" id="heure_sortie" name="heure_sortie" value="{{ old('heure_sortie', $pointage->heure_sortie) }}">
                    @error('heure_sortie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="heures_travaillees" class="form-label">Heures travaillees</label>
                    <input type="number" step="0.5" class="form-control @error('heures_travaillees') is-invalid @enderror" id="heures_travaillees" name="heures_travaillees" value="{{ old('heures_travaillees', $pointage->heures_travaillees) }}" placeholder="ex: 8.5">
                    @error('heures_travaillees')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="observations" class="form-label">Observations</label>
                    <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="4">{{ old('observations', $pointage->observations) }}</textarea>
                    @error('observations')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Enregistrer</button>
                    <a href="{{ route('rh.pointages.show', $pointage) }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
