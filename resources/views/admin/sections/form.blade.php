@extends('admin.layouts.sidebar')
@section('title', isset($section) ? 'Modifier section' : 'Nouvelle section')
@section('page-title', isset($section) ? 'Modifier : ' . $section->nom : 'Nouvelle section')

@section('topbar-actions')
<a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:540px">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ isset($section) ? route('admin.sections.update', $section) : route('admin.sections.store') }}">
            @csrf
            @if(isset($section)) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" maxlength="20"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $section->code ?? '') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom"
                           class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $section->nom ?? '') }}" required>
                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Département <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                        <option value="">– Choisir –</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}"
                            @selected(old('department_id', $section->department_id ?? '') == $dept->id)>
                            {{ $dept->nom }}
                        </option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $section->description ?? '') }}</textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        {{ isset($section) ? 'Mettre à jour' : 'Créer la section' }}
                    </button>
                    <a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
