@extends('layouts.app')

@section('title', 'Nouveau Pointage - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
@php /** @var \Illuminate\Support\Collection $agents */ @endphp
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-plus-circle me-2"></i>Nouveau pointage</h1>
                    <p class="rh-sub">Enregistrer les heures d'entree et de sortie d'un agent.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.pointages.index') }}" class="btn-rh alt"><i class="fas fa-arrow-left me-1"></i> Retour liste</a>
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

            <form action="{{ route('rh.pointages.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label for="agent_id" class="form-label">Agent</label>
                    <select class="form-select @error('agent_id') is-invalid @enderror" id="agent_id" name="agent_id" required>
                        <option value="">Selectionner un agent</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" @selected(old('agent_id') == $agent->id)>
                                ({{ $agent->id }}) {{ $agent->prenom }} {{ $agent->nom }}
                            </option>
                        @endforeach
                    </select>
                    @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="date_pointage" class="form-label">Date</label>
                    <input type="date" class="form-control @error('date_pointage') is-invalid @enderror" id="date_pointage" name="date_pointage" value="{{ old('date_pointage') }}" required>
                    @error('date_pointage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="heure_entree" class="form-label">Heure d'entree</label>
                    <input type="time" class="form-control @error('heure_entree') is-invalid @enderror" id="heure_entree" name="heure_entree" value="{{ old('heure_entree') }}">
                    @error('heure_entree')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="heure_sortie" class="form-label">Heure de sortie</label>
                    <input type="time" class="form-control @error('heure_sortie') is-invalid @enderror" id="heure_sortie" name="heure_sortie" value="{{ old('heure_sortie') }}">
                    @error('heure_sortie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="heures_travaillees" class="form-label">Heures travaillees</label>
                    <input type="number" step="0.5" class="form-control @error('heures_travaillees') is-invalid @enderror" id="heures_travaillees" name="heures_travaillees" value="{{ old('heures_travaillees') }}" placeholder="ex: 8.5">
                    @error('heures_travaillees')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12">
                    <label for="observations" class="form-label">Observations</label>
                    <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="3" placeholder="Ajouter des observations...">{{ old('observations') }}</textarea>
                    @error('observations')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Creer le pointage</button>
                    <a href="{{ route('rh.pointages.index') }}" class="btn btn-outline-secondary">Annuler</a>
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
