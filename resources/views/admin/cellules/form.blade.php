@extends('admin.layouts.sidebar')
@section('title', isset($cellule) ? 'Modifier cellule' : 'Nouvelle cellule')
@section('page-title', isset($cellule) ? 'Modifier : ' . $cellule->nom : 'Nouvelle cellule')

@section('topbar-actions')
<a href="{{ route('admin.cellules.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:540px">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ isset($cellule) ? route('admin.cellules.update', $cellule) : route('admin.cellules.store') }}">
            @csrf
            @if(isset($cellule)) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" maxlength="20"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $cellule->code ?? '') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom"
                           class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $cellule->nom ?? '') }}" required>
                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Section <span class="text-danger">*</span></label>
                    <select name="section_id" class="form-select @error('section_id') is-invalid @enderror" required>
                        <option value="">– Choisir –</option>
                        @foreach($sections as $section)
                        <optgroup label="{{ $section->department?->nom ?? 'Sans département' }}">
                            <option value="{{ $section->id }}"
                                @selected(old('section_id', $cellule->section_id ?? '') == $section->id)>
                                {{ $section->nom }} ({{ $section->code }})
                            </option>
                        </optgroup>
                        @endforeach
                    </select>
                    @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $cellule->description ?? '') }}</textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        {{ isset($cellule) ? 'Mettre à jour' : 'Créer la cellule' }}
                    </button>
                    <a href="{{ route('admin.cellules.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
