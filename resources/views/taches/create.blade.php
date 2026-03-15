@extends('layouts.app')

@section('title', 'Nouvelle Tache - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle Tache</h1>
                    <p class="rh-sub">Assigner une tache a un agent de votre departement.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('taches.index') }}" class="btn-rh alt">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="dash-panel mt-3">
            <div class="p-4">
                <form action="{{ route('taches.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        {{-- Agent --}}
                        <div class="col-md-6">
                            <label for="agent_id" class="form-label fw-bold">Assigner a <span class="text-danger">*</span></label>
                            <select class="form-select @error('agent_id') is-invalid @enderror" id="agent_id" name="agent_id" required>
                                <option value="">-- Choisir un agent --</option>
                                @foreach($agentsDuDepartement as $ag)
                                    <option value="{{ $ag->id }}" {{ old('agent_id') == $ag->id ? 'selected' : '' }}>
                                        {{ $ag->nom_complet }} ({{ $ag->id_agent }})
                                    </option>
                                @endforeach
                            </select>
                            @error('agent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Priorite --}}
                        <div class="col-md-3">
                            <label for="priorite" class="form-label fw-bold">Priorite <span class="text-danger">*</span></label>
                            <select class="form-select @error('priorite') is-invalid @enderror" id="priorite" name="priorite" required>
                                <option value="normale" {{ old('priorite', 'normale') === 'normale' ? 'selected' : '' }}>Normale</option>
                                <option value="haute" {{ old('priorite') === 'haute' ? 'selected' : '' }}>Haute</option>
                                <option value="urgente" {{ old('priorite') === 'urgente' ? 'selected' : '' }}>Urgente</option>
                            </select>
                            @error('priorite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Date echeance --}}
                        <div class="col-md-3">
                            <label for="date_echeance" class="form-label fw-bold">Echeance</label>
                            <input type="date" class="form-control @error('date_echeance') is-invalid @enderror" id="date_echeance" name="date_echeance" value="{{ old('date_echeance') }}">
                            @error('date_echeance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Titre --}}
                        <div class="col-12">
                            <label for="titre" class="form-label fw-bold">Titre de la tache <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('titre') is-invalid @enderror" id="titre" name="titre" value="{{ old('titre') }}" required placeholder="Ex: Preparer le rapport mensuel">
                            @error('titre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="col-12">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" placeholder="Details et instructions pour l'agent...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Boutons --}}
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Assigner la tache
                            </button>
                            <a href="{{ route('taches.index') }}" class="btn btn-outline-secondary ms-2">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
