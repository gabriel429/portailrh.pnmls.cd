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

                {{-- Champ unique pour le niveau (sera géré par JS) --}}
                <input type="hidden" name="niveau" id="hidden-niveau"
                       value="{{ old('niveau', $affectation->niveau ?? '') }}">

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
                        <div class="d-flex gap-2 mb-2">
                            <div class="flex-fill">
                                <input type="radio" class="btn-check" name="type_rattachement" id="ratt_dept" value="departement"
                                       @checked(old('type_rattachement', ($affectation->department_id ?? null) ? 'departement' : '') === 'departement')>
                                <label class="btn btn-outline-primary w-100 text-start" for="ratt_dept">
                                    <i class="fas fa-building me-1"></i> <strong>Département</strong><br>
                                    <small>L'agent est affecté dans un département</small>
                                </label>
                            </div>
                            <div class="flex-fill">
                                <input type="radio" class="btn-check" name="type_rattachement" id="ratt_service" value="service_rattache"
                                       @checked(old('type_rattachement', ($affectation->section_id ?? null) ? 'service_rattache' : '') === 'service_rattache')>
                                <label class="btn btn-outline-warning w-100 text-start" for="ratt_service">
                                    <i class="fas fa-link me-1"></i> <strong>Service rattaché</strong><br>
                                    <small>Rattaché directement au SEN/SENA</small>
                                </label>
                            </div>
                        </div>

                        {{-- Département --}}
                        <div id="panel-ratt-dept" style="display:none" class="mt-2">
                            <select name="department_id" id="sel-dept" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">– Choisir le département –</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    @selected(old('department_id', $affectation->department_id ?? '') == $dept->id)>
                                    {{ $dept->nom }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Service rattaché --}}
                        <div id="panel-ratt-service" style="display:none" class="mt-2">
                            <select name="section_id" id="sel-service-rattache" class="form-select @error('section_id') is-invalid @enderror">
                                <option value="">– Choisir le service rattaché –</option>
                                @foreach($sections->where('type', 'service_rattache') as $service)
                                <option value="{{ $service->id }}"
                                    @selected(old('section_id', $affectation->section_id ?? '') == $service->id)>
                                    {{ $service->nom }}
                                </option>
                                @endforeach
                            </select>
                            @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- SEP --}}
                    <div class="col-12 panel-na" id="panel-SEP" style="display:none">
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
    const naRadios     = document.querySelectorAll('[name="niveau_administratif"]');
    const naPanels     = { SEN: document.getElementById('panel-SEN'), SEP: document.getElementById('panel-SEP'), SEL: document.getElementById('panel-SEL') };
    const hiddenNiveau = document.getElementById('hidden-niveau');

    const fonctionSelect = document.querySelector('[name="fonction_id"]');

    function showNA(val) {
        Object.entries(naPanels).forEach(([k, el]) => { if (el) el.style.display = k === val ? '' : 'none'; });
        filterFonctions(val);
        updateNiveau();
    }

    // Filtrer les fonctions selon le niveau administratif sélectionné
    function filterFonctions(na) {
        if (!fonctionSelect) return;
        const optgroups = fonctionSelect.querySelectorAll('optgroup');
        optgroups.forEach(og => {
            const options = og.querySelectorAll('option');
            let hasVisible = false;
            options.forEach(opt => {
                const niveau = opt.getAttribute('data-niveau');
                const visible = (niveau === na || niveau === 'TOUS');
                opt.style.display = visible ? '' : 'none';
                opt.disabled = !visible;
                if (visible) hasVisible = true;
            });
            og.style.display = hasVisible ? '' : 'none';
        });
        // Reset si la valeur actuelle n'est plus visible
        const selected = fonctionSelect.querySelector('option:checked');
        if (selected && selected.disabled) {
            fonctionSelect.value = '';
        }
    }

    function updateNiveau() {
        const checkedNA = document.querySelector('[name="niveau_administratif"]:checked');
        if (!checkedNA) return;
        if (checkedNA.value === 'SEP') {
            hiddenNiveau.value = 'province';
        } else if (checkedNA.value === 'SEL') {
            hiddenNiveau.value = 'local';
        } else {
            const rattType = document.querySelector('[name="type_rattachement"]:checked');
            hiddenNiveau.value = rattType ? rattType.value : '';
        }
    }

    naRadios.forEach(r => r.addEventListener('change', () => showNA(r.value)));

    // SEN: toggle département / service rattaché panels
    const rattRadios = document.querySelectorAll('[name="type_rattachement"]');
    const panelDept = document.getElementById('panel-ratt-dept');
    const panelService = document.getElementById('panel-ratt-service');

    function showRattPanel() {
        const checked = document.querySelector('[name="type_rattachement"]:checked');
        if (!checked) {
            if (panelDept) panelDept.style.display = 'none';
            if (panelService) panelService.style.display = 'none';
            return;
        }
        if (panelDept) panelDept.style.display = checked.value === 'departement' ? '' : 'none';
        if (panelService) panelService.style.display = checked.value === 'service_rattache' ? '' : 'none';
        // Reset hidden fields when toggling
        if (checked.value === 'departement') {
            const selService = document.getElementById('sel-service-rattache');
            if (selService) selService.value = '';
        } else {
            const selDept = document.getElementById('sel-dept');
            if (selDept) selDept.value = '';
        }
        updateNiveau();
    }

    rattRadios.forEach(r => r.addEventListener('change', showRattPanel));

    // Init on page load
    const checkedNA = document.querySelector('[name="niveau_administratif"]:checked');
    if (checkedNA) {
        showNA(checkedNA.value);
    }
    showRattPanel();
    // Filtrage initial des fonctions
    if (checkedNA) filterFonctions(checkedNA.value);
})();
</script>
@endpush
@endsection
