@extends('admin.layouts.sidebar')
@section('title', isset($localite) ? 'Modifier localité' : 'Nouvelle localité')
@section('page-title', isset($localite) ? 'Modifier la localité' : 'Nouvelle localité SEL')

@section('topbar-actions')
<a href="{{ route('admin.localites.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:600px">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ isset($localite) ? route('admin.localites.update', $localite) : route('admin.localites.store') }}">
            @csrf
            @if(isset($localite)) @method('PUT') @endif

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $localite->code ?? '') }}" placeholder="EX: TER-KIN-01" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $localite->nom ?? '') }}" placeholder="Nom de la localité" required>
                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">– Choisir –</option>
                        @foreach(\App\Models\Localite::types() as $val => $label)
                        <option value="{{ $val }}" @selected(old('type', $localite->type ?? '') === $val)>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                    <select name="province_id" class="form-select @error('province_id') is-invalid @enderror" required>
                        <option value="">– Choisir –</option>
                        @foreach($provinces as $province)
                        <option value="{{ $province->id }}"
                            @selected(old('province_id', $localite->province_id ?? '') == $province->id)>
                            {{ $province->nom }}
                        </option>
                        @endforeach
                    </select>
                    @error('province_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Description facultative">{{ old('description', $localite->description ?? '') }}</textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        {{ isset($localite) ? 'Mettre à jour' : 'Enregistrer' }}
                    </button>
                    <a href="{{ route('admin.localites.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
