@extends('admin.layouts.sidebar')
@section('title', isset($grade) ? 'Modifier grade' : 'Nouveau grade')
@section('page-title', isset($grade) ? 'Modifier le grade' : 'Nouveau grade')

@section('topbar-actions')
<a href="{{ route('admin.grades.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="form-card" style="max-width:680px">
        <form method="POST"
              action="{{ isset($grade) ? route('admin.grades.update', $grade) : route('admin.grades.store') }}">
            @csrf
            @if(isset($grade)) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
                    <select name="categorie" class="form-select @error('categorie') is-invalid @enderror" required>
                        <option value="">–</option>
                        <option value="A" @selected(old('categorie', $grade->categorie ?? '') === 'A')>A – Haut cadre</option>
                        <option value="B" @selected(old('categorie', $grade->categorie ?? '') === 'B')>B – Collaboration</option>
                        <option value="C" @selected(old('categorie', $grade->categorie ?? '') === 'C')>C – Exécution</option>
                    </select>
                    @error('categorie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nom de la catégorie <span class="text-danger">*</span></label>
                    <input type="text" name="nom_categorie" class="form-control @error('nom_categorie') is-invalid @enderror"
                           value="{{ old('nom_categorie', $grade->nom_categorie ?? '') }}" required>
                    @error('nom_categorie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Ordre <span class="text-danger">*</span></label>
                    <input type="number" name="ordre" min="1"
                           class="form-control @error('ordre') is-invalid @enderror"
                           value="{{ old('ordre', $grade->ordre ?? '') }}" required>
                    @error('ordre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold">Intitulé du grade <span class="text-danger">*</span></label>
                    <input type="text" name="libelle" class="form-control @error('libelle') is-invalid @enderror"
                           value="{{ old('libelle', $grade->libelle ?? '') }}" required>
                    @error('libelle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        {{ isset($grade) ? 'Mettre à jour' : 'Créer le grade' }}
                    </button>
                    <a href="{{ route('admin.grades.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
</div>
@endsection
