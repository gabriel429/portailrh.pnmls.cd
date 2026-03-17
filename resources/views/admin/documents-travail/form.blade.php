@extends('admin.layouts.sidebar')
@section('title', isset($document) ? 'Modifier document' : 'Nouveau document de travail')
@section('page-title', isset($document) ? 'Modifier : ' . $document->titre : 'Nouveau document de travail')

@section('topbar-actions')
<a href="{{ route('admin.documents-travail.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="form-card" style="max-width:680px">
    <form method="POST" enctype="multipart/form-data"
          action="{{ isset($document) ? route('admin.documents-travail.update', $document) : route('admin.documents-travail.store') }}">
        @csrf
        @if(isset($document)) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                <input type="text" name="titre" class="form-control @error('titre') is-invalid @enderror"
                       value="{{ old('titre', $document->titre ?? '') }}" required placeholder="Ex: Règlement intérieur">
                @error('titre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
                <select name="categorie" class="form-select @error('categorie') is-invalid @enderror" required>
                    <option value="">– Choisir –</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(old('categorie', $document->categorie ?? '') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                @error('categorie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">
                    <a href="{{ route('admin.categories-documents.index') }}">Gérer les catégories</a>
                </small>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optionnel)</span></label>
                <textarea name="description" class="form-control" rows="3" placeholder="Brève description du document...">{{ old('description', $document->description ?? '') }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Fichier @if(!isset($document))<span class="text-danger">*</span>@endif</label>
                <input type="file" name="fichier" class="form-control @error('fichier') is-invalid @enderror"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                       {{ isset($document) ? '' : 'required' }}>
                @error('fichier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG — max 10 Mo</small>
                @if(isset($document) && $document->fichier)
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-file me-1"></i> {{ basename($document->fichier) }}
                            ({{ number_format(($document->taille ?? 0) / 1024 / 1024, 1) }} Mo)
                        </span>
                        <a href="{{ asset('storage/' . $document->fichier) }}" target="_blank" class="text-primary" style="font-size:.8rem;">
                            <i class="fas fa-external-link-alt"></i> Voir
                        </a>
                    </div>
                @endif
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="actif" value="1" id="actif" class="form-check-input"
                           {{ old('actif', $document->actif ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="actif">Actif (visible par les agents)</label>
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    {{ isset($document) ? 'Mettre à jour' : 'Ajouter' }}
                </button>
                <a href="{{ route('admin.documents-travail.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </form>
</div>
@endsection
