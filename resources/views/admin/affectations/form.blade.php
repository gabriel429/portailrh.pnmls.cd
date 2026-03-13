@extends('admin.layouts.sidebar')
@section('title', isset($affectation) ? 'Modifier affectation' : 'Nouvelle affectation')
@section('page-title', isset($affectation) ? 'Modifier l\'affectation' : 'Nouvelle affectation')

@section('topbar-actions')
<a href="{{ route('admin.affectations.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
    {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm" style="max-width:680px">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ isset($affectation) ? route('admin.affectations.update', $affectation) : route('admin.affectations.store') }}">
            @csrf
            @if(isset($affectation)) @method('PUT') @endif

            <div class="row g-3">

                {{-- Agent --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Agent <span class="text-danger">*</span></label>
                    <select name="agent_id" class="form-select @error('agent_id') is-invalid @enderror" required>
                        <option value="">– Choisir un agent –</option>
                        @foreach($agents as $agent)
                        <option value="{{ $agent->id }}"
                            @selected(old('agent_id', $affectation->agent_id ?? '') == $agent->id)>
                            {{ $agent->nom }} {{ $agent->postnom }} – {{ $agent->matricule ?? 'sans matricule' }}
                        </option>
                        @endforeach
                    </select>
                    @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Fonction --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Fonction <span class="text-danger">*</span></label>
                    <select name="fonction_id" class="form-select @error('fonction_id') is-invalid @enderror" required>
                        <option value="">– Choisir –</option>
                        @foreach($fonctions as $fonction)
                        <option value="{{ $fonction->id }}"
                            @selected(old('fonction_id', $affectation->fonction_id ?? '') == $fonction->id)>
                            {{ $fonction->nom }}
                            @if($fonction->est_chef) (Chef) @endif
                        </option>
                        @endforeach
                    </select>
                    @error('fonction_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Niveau --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Niveau d'affectation <span class="text-danger">*</span></label>
                    <select name="niveau" id="select-niveau"
                            class="form-select @error('niveau') is-invalid @enderror" required>
                        <option value="">– Choisir –</option>
                        <option value="département" @selected(old('niveau', $affectation->niveau ?? '') === 'département')>Département</option>
                        <option value="section"     @selected(old('niveau', $affectation->niveau ?? '') === 'section')>Section</option>
                        <option value="cellule"     @selected(old('niveau', $affectation->niveau ?? '') === 'cellule')>Cellule</option>
                    </select>
                    @error('niveau')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Département --}}
                <div class="col-12 niveau-panel" id="panel-departement" style="display:none">
                    <label class="form-label fw-semibold">Département <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">– Choisir –</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}"
                            @selected(old('department_id', $affectation->department_id ?? '') == $dept->id)>
                            {{ $dept->nom }}
                        </option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Section --}}
                <div class="col-12 niveau-panel" id="panel-section" style="display:none">
                    <label class="form-label fw-semibold">Section <span class="text-danger">*</span></label>
                    <select name="section_id" class="form-select @error('section_id') is-invalid @enderror">
                        <option value="">– Choisir –</option>
                        @foreach($sections as $section)
                        <option value="{{ $section->id }}"
                            @selected(old('section_id', $affectation->section_id ?? '') == $section->id)>
                            {{ $section->nom }} ({{ $section->department?->nom }})
                        </option>
                        @endforeach
                    </select>
                    @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Cellule --}}
                <div class="col-12 niveau-panel" id="panel-cellule" style="display:none">
                    <label class="form-label fw-semibold">Cellule <span class="text-danger">*</span></label>
                    <select name="cellule_id" class="form-select @error('cellule_id') is-invalid @enderror">
                        <option value="">– Choisir –</option>
                        @foreach($cellules as $cellule)
                        <option value="{{ $cellule->id }}"
                            @selected(old('cellule_id', $affectation->cellule_id ?? '') == $cellule->id)>
                            {{ $cellule->nom }} ({{ $cellule->section?->nom }})
                        </option>
                        @endforeach
                    </select>
                    @error('cellule_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Date début --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Date de début</label>
                    <input type="date" name="date_debut" class="form-control @error('date_debut') is-invalid @enderror"
                           value="{{ old('date_debut', isset($affectation) ? optional($affectation->date_debut)->format('Y-m-d') : '') }}">
                    @error('date_debut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Date fin --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Date de fin</label>
                    <input type="date" name="date_fin" class="form-control @error('date_fin') is-invalid @enderror"
                           value="{{ old('date_fin', isset($affectation) ? optional($affectation->date_fin)->format('Y-m-d') : '') }}">
                    @error('date_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Statut actif (edit seulement) --}}
                @if(isset($affectation))
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="actif" id="chk-actif" value="1"
                               @checked(old('actif', $affectation->actif ?? true))>
                        <label class="form-check-label fw-semibold" for="chk-actif">Affectation active</label>
                    </div>
                </div>
                @endif

                {{-- Remarque --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Remarque</label>
                    <textarea name="remarque" class="form-control" rows="2">{{ old('remarque', $affectation->remarque ?? '') }}</textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        {{ isset($affectation) ? 'Mettre à jour' : 'Enregistrer l\'affectation' }}
                    </button>
                    <a href="{{ route('admin.affectations.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const select  = document.getElementById('select-niveau');
    const panels  = {
        'département': document.getElementById('panel-departement'),
        'section':     document.getElementById('panel-section'),
        'cellule':     document.getElementById('panel-cellule'),
    };

    function showPanel(val) {
        Object.entries(panels).forEach(([key, el]) => {
            if (!el) return;
            const show = key === val;
            el.style.display = show ? '' : 'none';
            el.querySelectorAll('select').forEach(s => s.required = show);
        });
    }

    select.addEventListener('change', () => showPanel(select.value));
    // Init on page load
    showPanel(select.value);
})();
</script>
@endpush
@endsection
