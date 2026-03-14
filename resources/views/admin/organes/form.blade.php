@extends('admin.layouts.sidebar')

@section('title', 'Organes')
@section('page-title', isset($organe) ? 'Modifier un Organe' : 'Créer un Organe')

@section('content')

<div class="form-card">
    <form action="{{ isset($organe) ? route('admin.organes.update', $organe) : route('admin.organes.store') }}" method="POST">
        @csrf
        @if(isset($organe))
            @method('PUT')
        @endif

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $organe->code ?? '') }}" required maxlength="10"
                       placeholder="SEN, SEP, ou SEL">
                @error('code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Sigle <span class="text-muted">(optionnel)</span></label>
                <input type="text" name="sigle" class="form-control @error('sigle') is-invalid @enderror"
                       value="{{ old('sigle', $organe->sigle ?? '') }}" maxlength="30"
                       placeholder="ex: SEN">
                @error('sigle')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror"
                   value="{{ old('nom', $organe->nom ?? '') }}" required maxlength="255"
                   placeholder="ex: Secrétariat Exécutif National">
            @error('nom')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Description <span class="text-muted">(optionnel)</span></label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                      rows="4" placeholder="Décrivez l'organe...">{{ old('description', $organe->description ?? '') }}</textarea>
            @error('description')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="form-check">
                <input type="checkbox" name="actif" value="1" id="actif"
                       class="form-check-input" {{ old('actif', $organe->actif ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="actif">
                    Actif
                </label>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> {{ isset($organe) ? 'Mettre à jour' : 'Créer' }}
            </button>
            <a href="{{ route('admin.organes.index') }}" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i> Annuler
            </a>
        </div>
    </form>
</div>

@endsection
