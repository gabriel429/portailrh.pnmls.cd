@extends('layouts.app')

@section('title', (isset($affectation) ? 'Modifier affectation' : 'Nouvelle affectation') . ' - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title">
                        <i class="fas fa-user-tie me-2"></i>
                        {{ isset($affectation) ? 'Modifier l\'affectation' : 'Nouvelle affectation' }}
                    </h1>
                    <p class="rh-sub">{{ isset($affectation) ? 'Modifiez les détails de cette affectation.' : 'Affectez un agent à une fonction dans une structure.' }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.affectations.index') }}" class="btn-rh main"><i class="fas fa-arrow-left me-1"></i> Retour à la liste</a>
                    </div>
                </div>
            </div>
        </section>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3">
        {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm" style="max-width:720px">
        <div class="card-body p-4">
            <form method="POST"
                  action="{{ isset($affectation) ? route('rh.affectations.update', $affectation) : route('rh.affectations.store') }}">
                @csrf
                @if(isset($affectation)) @method('PUT') @endif

                <div class="row g-3">

                    {{-- Étape 1 : Niveau administratif --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <span class="badge bg-primary me-1">1</span>
                            Niveau administratif <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-2">
                            @foreach(['SEN' => ['Secrétariat Exécutif National','primary','fa-landmark'], 'SEP' => ['Secrétariat Exécutif Provincial','success','fa-map-marked-alt'], 'SEL' => ['Secrétariat Exécutif Local','info','fa-map-pin']] as $val => [$label, $color, $icon])
                            <div class="flex-fill">
                                <input type="radio" class="btn-check" name="niveau_administratif"
                                       id="na_{{ $val }}" value="{{ $val }}"
                                       @checked(old('niveau_administratif', $affectation->niveau_administratif ?? 'SEN') === $val)>
                                <label class="btn btn-outline-{{ $color }} w-100 text-start" for="na_{{ $val }}">
                                    <i class="fas {{ $icon }} me-2"></i>
                                    <strong>{{ $val }}</strong><br>
                                    <small>{{ $label }}</small>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('niveau_administratif')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- Étape 2 : Agent + Fonction --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <span class="badge bg-primary me-1">2</span>
                            Agent <span class="text-danger">*</span>
                        </label>
                        <select name="agent_id" class="form-select @error('agent_id') is-invalid @enderror" required>
                            <option value="">– Choisir un agent –</option>
                            @foreach($agents as $agent)
                            <option value="{{ $agent->id }}"
                                @selected(old('agent_id', $affectation->agent_id ?? '') == $agent->id)>
                                {{ $agent->nom }} {{ $agent->postnom }} – {{ $agent->id_agent }}
                            </option>
                            @endforeach
                        </select>
                        @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Fonction / Poste <span class="text-danger">*</span></label>
                        <select name="fonction_id" class="form-select @error('fonction_id') is-invalid @enderror" required>
                            <option value="">– Choisir –</option>
                            @foreach($fonctions->groupBy('niveau_administratif') as $na => $group)
                            <optgroup label="━━ {{ $na }} – {{ ['SEN'=>'National','SEP'=>'Provincial','SEL'=>'Local','TOUS'=>'Tous niveaux'][$na] ?? $na }} ━━">
                                @foreach($group as $fonction)
                                <option value="{{ $fonction->id }}"
                                    data-niveau="{{ $fonction->niveau_administratif }}"
                                    @selected(old('fonction_id', $affectation->fonction_id ?? '') == $fonction->id)>
                                    {{ $fonction->nom }}@if($fonction->est_chef) ★@endif
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        @error('fonction_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Étape 3 : Rattachement structurel -- SEN --}}
                    <div class="col-12 panel-na" id="panel-SEN">
                        <label class="form-label fw-semibold">
                            <span class="badge bg-primary me-1">3</span>
                            Rattachement SEN <span class="text-danger">*</span>
                        </label>
                        <select name="niveau" id="select-niveau-sen"
                                class="form-select @error('niveau') is-invalid @enderror">
                            <option value="">– Choisir le type de rattachement –</option>
                            @php
                            $niveauxSEN = [
                                'direction'        => 'Direction (SEN / SENA)',
                                'service_rattache' => 'Service rattaché au SEN/SENA',
                                'département'      => 'Département (sans section)',
                                'section'          => 'Section (dans un département)',
                                'cellule'          => 'Cellule (dans une section)',
                            ];
                            $currentNiveau = old('niveau', $affectation->niveau ?? '');
                            @endphp
                            @foreach($niveauxSEN as $val => $label)
                            <option value="{{ $val }}" @selected($currentNiveau === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('niveau')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        <div class="sen-panel mt-2" id="senpanel-departement" style="display:none">
                            <label class="form-label fw-semibold">Département</label>
                            <select name="department_id" id="sel-dept" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">– Choisir –</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    @selected(old('department_id', $affectation->department_id ?? '') == $dept->id)>
                                    {{ $dept->nom }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sen-panel mt-2" id="senpanel-section" style="display:none">
                            <label class="form-label fw-semibold">Section / Service rattaché</label>
                            <select name="section_id" id="sel-section" class="form-select @error('section_id') is-invalid @enderror">
                                <option value="">– Choisir –</option>
                                @foreach($sections as $section)
                                <option value="{{ $section->id }}"
                                    data-type="{{ $section->type }}"
                                    @selected(old('section_id', $affectation->section_id ?? '') == $section->id)>
                                    @if($section->type === 'service_rattache')
                                        [Service rattaché] {{ $section->nom }}
                                    @else
                                        {{ $section->nom }} ({{ $section->department?->nom }})
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sen-panel mt-2" id="senpanel-cellule" style="display:none">
                            <label class="form-label fw-semibold">Cellule</label>
                            <select name="cellule_id" class="form-select @error('cellule_id') is-invalid @enderror">
                                <option value="">– Choisir –</option>
                                @foreach($cellules as $cellule)
                                <option value="{{ $cellule->id }}"
                                    @selected(old('cellule_id', $affectation->cellule_id ?? '') == $cellule->id)>
                                    {{ $cellule->nom }} → {{ $cellule->section?->nom }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- SEP --}}
                    <div class="col-12 panel-na" id="panel-SEP" style="display:none">
                        <input type="hidden" name="niveau" value="province">
                        <label class="form-label fw-semibold">
                            <span class="badge bg-success me-1">3</span>
                            Province (SEP) <span class="text-danger">*</span>
                        </label>
                        <select name="province_id" class="form-select @error('province_id') is-invalid @enderror">
                            <option value="">– Choisir la province –</option>
                            @foreach($provinces as $province)
                            <option value="{{ $province->id }}"
                                @selected(old('province_id', $affectation->province_id ?? '') == $province->id)>
                                {{ $province->nom }} ({{ $province->code }})
                            </option>
                            @endforeach
                        </select>
                        @error('province_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- SEL --}}
                    <div class="col-12 panel-na" id="panel-SEL" style="display:none">
                        <input type="hidden" name="niveau" value="local">
                        <label class="form-label fw-semibold">
                            <span class="badge bg-info me-1">3</span>
                            Localité (SEL) <span class="text-danger">*</span>
                        </label>
                        <select name="localite_id" class="form-select @error('localite_id') is-invalid @enderror">
                            <option value="">– Choisir la localité –</option>
                            @foreach($localites as $loc)
                            <option value="{{ $loc->id }}"
                                @selected(old('localite_id', $affectation->localite_id ?? '') == $loc->id)>
                                {{ $loc->nom }} – {{ $loc->province?->nom }}
                            </option>
                            @endforeach
                        </select>
                        @error('localite_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Dates + statut --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date de début</label>
                        <input type="date" name="date_debut" class="form-control @error('date_debut') is-invalid @enderror"
                               value="{{ old('date_debut', isset($affectation) ? optional($affectation->date_debut)->format('Y-m-d') : '') }}">
                        @error('date_debut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date de fin</label>
                        <input type="date" name="date_fin" class="form-control @error('date_fin') is-invalid @enderror"
                               value="{{ old('date_fin', isset($affectation) ? optional($affectation->date_fin)->format('Y-m-d') : '') }}">
                        @error('date_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if(isset($affectation))
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" id="chk-actif" value="1"
                                   @checked(old('actif', $affectation->actif ?? true))>
                            <label class="form-check-label fw-semibold" for="chk-actif">Affectation active</label>
                        </div>
                    </div>
                    @endif

                    <div class="col-12">
                        <label class="form-label fw-semibold">Remarque</label>
                        <textarea name="remarque" class="form-control" rows="2">{{ old('remarque', $affectation->remarque ?? '') }}</textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            {{ isset($affectation) ? 'Mettre à jour' : 'Enregistrer l\'affectation' }}
                        </button>
                        <a href="{{ route('rh.affectations.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const naRadios  = document.querySelectorAll('[name="niveau_administratif"]');
    const naPanels  = { SEN: document.getElementById('panel-SEN'), SEP: document.getElementById('panel-SEP'), SEL: document.getElementById('panel-SEL') };

    function showNA(val) {
        Object.entries(naPanels).forEach(([k, el]) => { if (el) el.style.display = k === val ? '' : 'none'; });
        const selNiveauSEN = document.getElementById('select-niveau-sen');
        if (selNiveauSEN) selNiveauSEN.required = val === 'SEN';
    }

    naRadios.forEach(r => r.addEventListener('change', () => showNA(r.value)));

    const selectNiveauSEN = document.getElementById('select-niveau-sen');

    function showSENPanel(val) {
        ['senpanel-departement','senpanel-section','senpanel-cellule'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        document.querySelectorAll('.sen-panel select').forEach(s => s.required = false);

        const map = {
            'département':      'senpanel-departement',
            'section':          'senpanel-section',
            'cellule':          'senpanel-cellule',
            'service_rattache': 'senpanel-section',
        };
        const shown = map[val];
        if (shown) {
            const el = document.getElementById(shown);
            if (el) {
                el.style.display = '';
                const sel = el.querySelector('select');
                if (sel) sel.required = true;
            }
        }
        if (val === 'cellule') {
            const secEl = document.getElementById('senpanel-section');
            if (secEl) { secEl.style.display = ''; document.getElementById('sel-section').required = true; }
        }
    }

    if (selectNiveauSEN) {
        selectNiveauSEN.addEventListener('change', () => showSENPanel(selectNiveauSEN.value));
    }

    const checkedNA = document.querySelector('[name="niveau_administratif"]:checked');
    if (checkedNA) {
        showNA(checkedNA.value);
        if (checkedNA.value === 'SEN' && selectNiveauSEN) {
            showSENPanel(selectNiveauSEN.value);
        }
    }
})();
</script>
@endpush
@endsection
