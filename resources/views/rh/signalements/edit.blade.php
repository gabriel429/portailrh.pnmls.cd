@extends('layouts.app')

@section('title', 'Modifier Signalement - Portail RH PNMLS')

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
                    <h1 class="rh-title"><i class="fas fa-edit me-2"></i>Modifier le signalement</h1>
                    <p class="rh-sub">Mise a jour du dossier #{{ $signalement->id }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('signalements.show', $signalement) }}" class="btn-rh alt"><i class="fas fa-arrow-left me-1"></i> Retour details</a>
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

            <form action="{{ route('signalements.update', $signalement) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">Agent</label>
                    <select class="form-select" disabled>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected($signalement->agent_id == $agent->id)>
                                ({{ $agent->id_agent }}) {{ $agent->prenom }} {{ $agent->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="type" class="form-label">Type</label>
                    <input type="text" class="form-control @error('type') is-invalid @enderror" id="type" name="type" value="{{ old('type', $signalement->type) }}" required>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="severite" class="form-label">Severite</label>
                    <select class="form-select @error('severite') is-invalid @enderror" id="severite" name="severite" required>
                        <option value="basse" @selected(old('severite', $signalement->severite) === 'basse')>Basse</option>
                        <option value="moyenne" @selected(old('severite', $signalement->severite) === 'moyenne')>Moyenne</option>
                        <option value="haute" @selected(old('severite', $signalement->severite) === 'haute')>Haute</option>
                    </select>
                    @error('severite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                        <option value="ouvert" @selected(old('statut', $signalement->statut) === 'ouvert')>Ouvert</option>
                        <option value="en_cours" @selected(old('statut', $signalement->statut) === 'en_cours')>En cours</option>
                        <option value="résolu" @selected(old('statut', $signalement->statut) === 'résolu')>Resolu</option>
                        <option value="fermé" @selected(old('statut', $signalement->statut) === 'fermé')>Ferme</option>
                    </select>
                    @error('statut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description', $signalement->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="observations" class="form-label">Observations</label>
                    <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="3">{{ old('observations', $signalement->observations) }}</textarea>
                    @error('observations')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Enregistrer</button>
                    <a href="{{ route('signalements.show', $signalement) }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
