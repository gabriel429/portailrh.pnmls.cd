@extends('layouts.app')

@section('title', isset($communique) ? 'Modifier Communiqué' : 'Nouveau Communiqué')

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
                        <i class="fas fa-bullhorn me-2"></i>
                        {{ isset($communique) ? 'Modifier le Communiqué' : 'Nouveau Communiqué' }}
                    </h1>
                    <p class="rh-sub">{{ isset($communique) ? 'Modifier ce communiqué officiel.' : 'Publier un communiqué à destination de tous les agents.' }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.communiques.index') }}" class="btn-rh alt">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="dash-panel mt-3">
            <div class="p-4">
                <form action="{{ isset($communique) ? route('rh.communiques.update', $communique) : route('rh.communiques.store') }}" method="POST">
                    @csrf
                    @if(isset($communique))
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        {{-- Titre --}}
                        <div class="col-12">
                            <label for="titre" class="form-label fw-bold">Titre du communiqué <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('titre') is-invalid @enderror" id="titre" name="titre" value="{{ old('titre', $communique->titre ?? '') }}" required placeholder="Ex: Communiqué relatif aux congés annuels">
                            @error('titre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Contenu --}}
                        <div class="col-12">
                            <label for="contenu" class="form-label fw-bold">Contenu <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('contenu') is-invalid @enderror" id="contenu" name="contenu" rows="8" required placeholder="Rédigez le contenu du communiqué...">{{ old('contenu', $communique->contenu ?? '') }}</textarea>
                            @error('contenu')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Urgence --}}
                        <div class="col-md-4">
                            <label for="urgence" class="form-label fw-bold">Niveau d'urgence <span class="text-danger">*</span></label>
                            <select class="form-select @error('urgence') is-invalid @enderror" id="urgence" name="urgence" required>
                                <option value="normal" {{ old('urgence', $communique->urgence ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="important" {{ old('urgence', $communique->urgence ?? '') === 'important' ? 'selected' : '' }}>Important</option>
                                <option value="urgent" {{ old('urgence', $communique->urgence ?? '') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                            @error('urgence')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Signataire --}}
                        <div class="col-md-4">
                            <label for="signataire" class="form-label fw-bold">Signataire (au nom de)</label>
                            <input type="text" class="form-control @error('signataire') is-invalid @enderror" id="signataire" name="signataire" value="{{ old('signataire', $communique->signataire ?? '') }}" placeholder="Ex: Le Secrétaire Exécutif National">
                            @error('signataire')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Date expiration --}}
                        <div class="col-md-4">
                            <label for="date_expiration" class="form-label fw-bold">Date d'expiration</label>
                            <input type="date" class="form-control @error('date_expiration') is-invalid @enderror" id="date_expiration" name="date_expiration" value="{{ old('date_expiration', isset($communique) && $communique->date_expiration ? $communique->date_expiration->format('Y-m-d') : '') }}">
                            <small class="text-muted">Laisser vide pour un communiqué sans expiration.</small>
                            @error('date_expiration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Actif --}}
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" {{ old('actif', $communique->actif ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="actif">
                                    Publier immédiatement (actif)
                                </label>
                            </div>
                        </div>

                        {{-- Boutons --}}
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>
                                {{ isset($communique) ? 'Mettre à jour' : 'Publier le communiqué' }}
                            </button>
                            <a href="{{ route('rh.communiques.index') }}" class="btn btn-outline-secondary ms-2">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
