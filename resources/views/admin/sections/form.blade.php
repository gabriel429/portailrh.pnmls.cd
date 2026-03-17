@extends('admin.layouts.sidebar')
@section('title', isset($section) ? 'Modifier section' : 'Nouvelle section')
@section('page-title', isset($section) ? 'Modifier : ' . $section->nom : 'Nouvelle section')

@section('topbar-actions')
<a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="form-card" style="max-width:680px">
        <form method="POST"
              action="{{ isset($section) ? route('admin.sections.update', $section) : route('admin.sections.store') }}">
            @csrf
            @if(isset($section)) @method('PUT') @endif

            <div class="row g-3">
                {{-- Type de section --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="type" id="type-section" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="section"          @selected(old('type', $section->type ?? 'section') === 'section')>
                            Section de département (SEN)
                        </option>
                        <option value="service_rattache" @selected(old('type', $section->type ?? '') === 'service_rattache')>
                            Service rattaché directement au SEN/SENA
                        </option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

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

                {{-- Département (masqué si service rattaché) --}}
                <div class="col-12" id="panel-dept">
                    <label class="form-label fw-semibold">Département <span class="text-danger">*</span></label>
                    <select name="department_id" id="sel-dept" class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">– Choisir le département –</option>
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
                        {{ isset($section) ? 'Mettre à jour' : 'Créer' }}
                    </button>
                    <a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
</div>
@endsection

@section('js')
<script>
(function () {
    const typeSelect = document.getElementById('type-section');
    const panelDept  = document.getElementById('panel-dept');
    const selDept    = document.getElementById('sel-dept');

    function toggle() {
        const isRattache = typeSelect.value === 'service_rattache';
        panelDept.style.display = isRattache ? 'none' : '';
        selDept.required = !isRattache;
    }

    typeSelect.addEventListener('change', toggle);
    toggle();
})();
</script>
@endsection
