@extends('layouts.app')

@section('title', isset($activitePlan) ? 'Modifier l\'activite' : 'Nouvelle activite - PTA')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title">
                        <i class="fas fa-{{ isset($activitePlan) ? 'edit' : 'plus-circle' }} me-2"></i>
                        {{ isset($activitePlan) ? 'Modifier l\'activite' : 'Nouvelle activite' }}
                    </h1>
                    <p class="rh-sub">Plan de Travail Annuel {{ isset($activitePlan) ? $activitePlan->annee : ($annee ?? now()->year) }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('plan-travail.index') }}" class="btn-rh alt">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
        </section>

        @if($errors->any())
            <div class="alert alert-danger mt-3">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="dash-panel mt-3">
            <div class="p-4">
                <form action="{{ isset($activitePlan) ? route('plan-travail.update', $activitePlan) : route('plan-travail.store') }}" method="POST">
                    @csrf
                    @if(isset($activitePlan))
                        @method('PUT')
                    @endif

                    {{-- Titre --}}
                    <div class="mb-3">
                        <label for="titre" class="form-label fw-bold">Titre de l'activite <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('titre') is-invalid @enderror" id="titre" name="titre" value="{{ old('titre', $activitePlan->titre ?? '') }}" required>
                        @error('titre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        {{-- Niveau administratif --}}
                        <div class="col-md-4">
                            <label for="niveau_administratif" class="form-label fw-bold">Niveau <span class="text-danger">*</span></label>
                            <select class="form-select @error('niveau_administratif') is-invalid @enderror" id="niveau_administratif" name="niveau_administratif" required>
                                <option value="">-- Choisir --</option>
                                <option value="SEN" {{ old('niveau_administratif', $activitePlan->niveau_administratif ?? '') === 'SEN' ? 'selected' : '' }}>SEN (National)</option>
                                <option value="SEP" {{ old('niveau_administratif', $activitePlan->niveau_administratif ?? '') === 'SEP' ? 'selected' : '' }}>SEP (Provincial)</option>
                                <option value="SEL" {{ old('niveau_administratif', $activitePlan->niveau_administratif ?? '') === 'SEL' ? 'selected' : '' }}>SEL (Local)</option>
                            </select>
                            @error('niveau_administratif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Departement (SEN) --}}
                        <div class="col-md-4" id="departement-group">
                            <label for="departement_id" class="form-label fw-bold">Departement</label>
                            <select class="form-select" id="departement_id" name="departement_id">
                                <option value="">-- Direction / Tous --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('departement_id', $activitePlan->departement_id ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Province (SEP/SEL) --}}
                        <div class="col-md-4" id="province-group" style="display: none;">
                            <label for="province_id" class="form-label fw-bold">Province</label>
                            <select class="form-select" id="province_id" name="province_id">
                                <option value="">-- Choisir --</option>
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov->id }}" {{ old('province_id', $activitePlan->province_id ?? '') == $prov->id ? 'selected' : '' }}>{{ $prov->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Localite (SEL) --}}
                        <div class="col-md-4" id="localite-group" style="display: none;">
                            <label for="localite_id" class="form-label fw-bold">Localite</label>
                            <select class="form-select" id="localite_id" name="localite_id">
                                <option value="">-- Choisir --</option>
                                @foreach($localites as $loc)
                                    <option value="{{ $loc->id }}" {{ old('localite_id', $activitePlan->localite_id ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        {{-- Annee --}}
                        <div class="col-md-3">
                            <label for="annee" class="form-label fw-bold">Annee <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('annee') is-invalid @enderror" id="annee" name="annee" value="{{ old('annee', $activitePlan->annee ?? $annee ?? now()->year) }}" min="2020" max="2040" required>
                            @error('annee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Trimestre --}}
                        <div class="col-md-3">
                            <label for="trimestre" class="form-label fw-bold">Trimestre</label>
                            <select class="form-select" id="trimestre" name="trimestre">
                                <option value="">Annuel</option>
                                <option value="T1" {{ old('trimestre', $activitePlan->trimestre ?? '') === 'T1' ? 'selected' : '' }}>T1 (Jan-Mar)</option>
                                <option value="T2" {{ old('trimestre', $activitePlan->trimestre ?? '') === 'T2' ? 'selected' : '' }}>T2 (Avr-Jun)</option>
                                <option value="T3" {{ old('trimestre', $activitePlan->trimestre ?? '') === 'T3' ? 'selected' : '' }}>T3 (Jul-Sep)</option>
                                <option value="T4" {{ old('trimestre', $activitePlan->trimestre ?? '') === 'T4' ? 'selected' : '' }}>T4 (Oct-Dec)</option>
                            </select>
                        </div>

                        {{-- Statut --}}
                        <div class="col-md-3">
                            <label for="statut" class="form-label fw-bold">Statut <span class="text-danger">*</span></label>
                            <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                                <option value="planifiee" {{ old('statut', $activitePlan->statut ?? 'planifiee') === 'planifiee' ? 'selected' : '' }}>Planifiee</option>
                                <option value="en_cours" {{ old('statut', $activitePlan->statut ?? '') === 'en_cours' ? 'selected' : '' }}>En cours</option>
                                <option value="terminee" {{ old('statut', $activitePlan->statut ?? '') === 'terminee' ? 'selected' : '' }}>Terminee</option>
                            </select>
                            @error('statut') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Pourcentage --}}
                        <div class="col-md-3">
                            <label for="pourcentage" class="form-label fw-bold">Progression (%)</label>
                            <input type="number" class="form-control" id="pourcentage" name="pourcentage" value="{{ old('pourcentage', $activitePlan->pourcentage ?? 0) }}" min="0" max="100">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        {{-- Date debut --}}
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label fw-bold">Date de debut</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ old('date_debut', isset($activitePlan) && $activitePlan->date_debut ? $activitePlan->date_debut->format('Y-m-d') : '') }}">
                        </div>

                        {{-- Date fin --}}
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label fw-bold">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ old('date_fin', isset($activitePlan) && $activitePlan->date_fin ? $activitePlan->date_fin->format('Y-m-d') : '') }}">
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3 mt-3">
                        <label for="description" class="form-label fw-bold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $activitePlan->description ?? '') }}</textarea>
                    </div>

                    {{-- Observations --}}
                    <div class="mb-3">
                        <label for="observations" class="form-label fw-bold">Observations</label>
                        <textarea class="form-control" id="observations" name="observations" rows="3">{{ old('observations', $activitePlan->observations ?? '') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> {{ isset($activitePlan) ? 'Mettre a jour' : 'Creer l\'activite' }}
                        </button>
                        <a href="{{ route('plan-travail.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const niveauSelect = document.getElementById('niveau_administratif');
    const deptGroup = document.getElementById('departement-group');
    const provGroup = document.getElementById('province-group');
    const locGroup = document.getElementById('localite-group');

    function toggleFields() {
        const val = niveauSelect.value;
        deptGroup.style.display = (val === 'SEN') ? '' : 'none';
        provGroup.style.display = (val === 'SEP' || val === 'SEL') ? '' : 'none';
        locGroup.style.display = (val === 'SEL') ? '' : 'none';

        if (val !== 'SEN') document.getElementById('departement_id').value = '';
        if (val !== 'SEP' && val !== 'SEL') document.getElementById('province_id').value = '';
        if (val !== 'SEL') document.getElementById('localite_id').value = '';
    }

    niveauSelect.addEventListener('change', toggleFields);
    toggleFields();
});
</script>
@endsection
