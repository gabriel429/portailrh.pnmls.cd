@extends('admin.layouts.sidebar')
@section('title', isset($fonction) ? 'Modifier fonction' : 'Nouvelle fonction')
@section('page-title', isset($fonction) ? 'Modifier : ' . $fonction->nom : 'Nouvelle fonction')

@section('topbar-actions')
<a href="{{ route('admin.fonctions.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:540px">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ isset($fonction) ? route('admin.fonctions.update', $fonction) : route('admin.fonctions.store') }}">
            @csrf
            @if(isset($fonction)) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Nom de la fonction <span class="text-danger">*</span></label>
                    <input type="text" name="nom"
                           class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $fonction->nom ?? '') }}" required>
                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Niveau <span class="text-danger">*</span></label>
                    <select name="niveau" class="form-select @error('niveau') is-invalid @enderror" required>
                        <option value="">– Choisir –</option>
                        @foreach(['département','section','cellule','transversal'] as $niv)
                        <option value="{{ $niv }}" @selected(old('niveau', $fonction->niveau ?? '') === $niv)>
                            {{ ucfirst($niv) }}
                        </option>
                        @endforeach
                    </select>
                    @error('niveau')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="est_chef" id="est_chef" value="1"
                               @checked(old('est_chef', $fonction->est_chef ?? false))>
                        <label class="form-check-label fw-semibold" for="est_chef">
                            <i class="fas fa-crown text-warning me-1"></i>Poste de responsable (chef unique)
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $fonction->description ?? '') }}</textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        {{ isset($fonction) ? 'Mettre à jour' : 'Créer la fonction' }}
                    </button>
                    <a href="{{ route('admin.fonctions.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
