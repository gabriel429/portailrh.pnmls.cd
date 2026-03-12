@extends('admin.layouts.sidebar')
@section('title', isset($province) ? 'Modifier province' : 'Nouvelle province')
@section('page-title', isset($province) ? 'Modifier : ' . $province->nom : 'Nouvelle province')

@section('topbar-actions')
<a href="{{ route('admin.provinces.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:800px">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ isset($province)
                ? route('admin.provinces.update', $province)
                : route('admin.provinces.store') }}">
            @csrf
            @if(isset($province)) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $province->code ?? '') }}" placeholder="ex: KIN" maxlength="10" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold">Nom de la province <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $province->nom ?? '') }}" required>
                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $province->description ?? '') }}</textarea>
                </div>

                <div class="col-12"><hr class="my-1"><p class="fw-semibold text-primary mb-0"><i class="fas fa-building me-2"></i>Secrétariat provincial / local</p></div>

                <div class="col-md-6">
                    <label class="form-label">Ville du secrétariat</label>
                    <input type="text" name="ville_secretariat" class="form-control"
                           value="{{ old('ville_secretariat', $province->ville_secretariat ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Adresse complète</label>
                    <input type="text" name="adresse" class="form-control"
                           value="{{ old('adresse', $province->adresse ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom du Gouverneur</label>
                    <input type="text" name="nom_gouverneur" class="form-control"
                           value="{{ old('nom_gouverneur', $province->nom_gouverneur ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nom du Secrétariat Exécutif</label>
                    <input type="text" name="nom_secretariat_executif" class="form-control"
                           value="{{ old('nom_secretariat_executif', $province->nom_secretariat_executif ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mail officiel</label>
                    <input type="email" name="email_officiel" class="form-control @error('email_officiel') is-invalid @enderror"
                           value="{{ old('email_officiel', $province->email_officiel ?? '') }}">
                    @error('email_officiel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Numéro officiel</label>
                    <input type="text" name="telephone_officiel" class="form-control"
                           value="{{ old('telephone_officiel', $province->telephone_officiel ?? '') }}">
                </div>

                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        {{ isset($province) ? 'Mettre à jour' : 'Créer la province' }}
                    </button>
                    <a href="{{ route('admin.provinces.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
