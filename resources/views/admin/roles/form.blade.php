@extends('admin.layouts.sidebar')
@section('title', isset($role) ? 'Modifier rôle' : 'Nouveau rôle')
@section('page-title', isset($role) ? 'Modifier : ' . $role->nom_role : 'Nouveau rôle')

@section('topbar-actions')
<a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="form-card" style="max-width:680px">
        <form method="POST"
              action="{{ isset($role) ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
            @csrf
            @if(isset($role)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Nom du rôle <span class="text-danger">*</span></label>
                <input type="text" name="nom_role" class="form-control @error('nom_role') is-invalid @enderror"
                       value="{{ old('nom_role', $role->nom_role ?? '') }}" required>
                @error('nom_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $role->description ?? '') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    {{ isset($role) ? 'Mettre à jour' : 'Créer le rôle' }}
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
</div>
@endsection
