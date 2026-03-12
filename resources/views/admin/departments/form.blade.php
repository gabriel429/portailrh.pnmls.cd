@extends('admin.layouts.sidebar')
@section('title', isset($department) ? 'Modifier département' : 'Nouveau département')
@section('page-title', isset($department) ? 'Modifier : ' . $department->nom : 'Nouveau département')

@section('topbar-actions')
<a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:540px">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ isset($department)
                ? route('admin.departments.update', $department)
                : route('admin.departments.store') }}">
            @csrf
            @if(isset($department)) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" maxlength="10"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $department->code ?? '') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom"
                           class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $department->nom ?? '') }}" required>
                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Province</label>
                    <select name="province_id" class="form-select">
                        <option value="">– Aucune –</option>
                        @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}"
                            @selected(old('province_id', $department->province_id ?? '') == $prov->id)>
                            {{ $prov->nom }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $department->description ?? '') }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        {{ isset($department) ? 'Mettre à jour' : 'Créer le département' }}
                    </button>
                    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
