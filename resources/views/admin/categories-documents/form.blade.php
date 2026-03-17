@extends('admin.layouts.sidebar')
@section('title', isset($categorie) ? 'Modifier catégorie' : 'Nouvelle catégorie')
@section('page-title', isset($categorie) ? 'Modifier : ' . $categorie->nom : 'Nouvelle catégorie')

@section('topbar-actions')
<a href="{{ route('admin.categories-documents.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="form-card" style="max-width:520px">
    <form method="POST"
          action="{{ isset($categorie) ? route('admin.categories-documents.update', $categorie) : route('admin.categories-documents.store') }}">
        @csrf
        @if(isset($categorie)) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">Nom de la catégorie <span class="text-danger">*</span></label>
                <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror"
                       value="{{ old('nom', $categorie->nom ?? '') }}" required placeholder="Ex: Note de service">
                @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Icône FontAwesome <span class="text-muted fw-normal">(optionnel)</span></label>
                <div class="input-group">
                    <span class="input-group-text" id="icon-preview">
                        <i class="fas {{ old('icone', $categorie->icone ?? 'fa-folder') }}"></i>
                    </span>
                    <input type="text" name="icone" class="form-control @error('icone') is-invalid @enderror"
                           value="{{ old('icone', $categorie->icone ?? '') }}" placeholder="fa-folder" id="icone-input">
                </div>
                @error('icone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <small class="form-text text-muted">Ex: fa-gavel, fa-book, fa-bullhorn, fa-file-lines, fa-chart-bar</small>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="actif" value="1" id="actif" class="form-check-input"
                           {{ old('actif', $categorie->actif ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="actif">Active (visible dans les formulaires)</label>
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    {{ isset($categorie) ? 'Mettre à jour' : 'Créer' }}
                </button>
                <a href="{{ route('admin.categories-documents.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js')
<script>
document.getElementById('icone-input').addEventListener('input', function() {
    const icon = this.value.trim() || 'fa-folder';
    document.querySelector('#icon-preview i').className = 'fas ' + icon;
});
</script>
@endsection
