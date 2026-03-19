@extends('layouts.app')

@php
/** @var \Illuminate\Support\Collection $departments */
/** @var \Illuminate\Support\Collection $provinces */
/** @var array $organeOptions */
/** @var array $fonctionOptions */
/** @var \Illuminate\Support\Collection $grades */
/** @var \Illuminate\Support\Collection $institutionCategories */
@endphp

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-user-edit me-2"></i> Modifier l'Agent</h2>
            <p class="text-muted mb-0">{{ $agent->prenom }} {{ $agent->nom }}</p>
        </div>
        <a href="{{ route('rh.agents.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Retour
        </a>
    </div>

    <!-- Formulaire -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreurs de validation:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('rh.agents.update', $agent) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Photo de profil -->
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-camera me-2 text-primary"></i>Photo de profil</h5>
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            @if($agent->photo)
                                <img src="{{ asset($agent->photo) }}" alt="{{ $agent->prenom }}" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                            @else
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col">
                            <input type="file" class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" accept="image/*">
                            @error('photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Max 2 Mo. Formats: JPG, PNG, GIF</small>
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Informations personnelles -->
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-user me-2 text-primary"></i>Informations Personnelles</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ID Agent</label>
                            <p class="form-control-plaintext fw-bold">{{ $agent->id_agent }}</p>
                        </div>


                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('prenom') is-invalid @enderror"
                                id="prenom" name="prenom" value="{{ old('prenom', $agent->prenom) }}" required>
                            @error('prenom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nom') is-invalid @enderror"
                                id="nom" name="nom" value="{{ old('nom', $agent->nom) }}" required>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="postnom" class="form-label">Post nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('postnom') is-invalid @enderror"
                                id="postnom" name="postnom" value="{{ old('postnom', $agent->postnom) }}" required>
                            @error('postnom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="matricule_etat" class="form-label">Matricule de l'État</label>
                            <input type="text" class="form-control @error('matricule_etat') is-invalid @enderror"
                                id="matricule_etat" name="matricule_etat" value="{{ old('matricule_etat', $agent->matricule_etat) }}" placeholder="Laisser vide si inconnu">
                            <small class="text-muted">Optionnel</small>
                            @error('matricule_etat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="institution_id" class="form-label">Provenance matricule</label>
                            <select class="form-select @error('institution_id') is-invalid @enderror"
                                    id="institution_id" name="institution_id">
                                <option value="">-- Sélectionner une institution --</option>
                                @foreach ($institutionCategories as $category)
                                    <optgroup label="{{ $category->nom }}">
                                        @foreach ($category->institutions as $institution)
                                            <option value="{{ $institution->id }}" @selected(old('institution_id', $agent->institution_id) == $institution->id)>
                                                {{ $institution->nom }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('institution_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="grade_id" class="form-label">Grade de l'État</label>
                            <select class="form-select @error('grade_id') is-invalid @enderror"
                                    id="grade_id" name="grade_id">
                                <option value="">-- Sélectionner un grade --</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}" @selected(old('grade_id', $agent->grade_id) == $grade->id)>
                                        {{ $grade->libelle }} ({{ $grade->categorie }})
                                    </option>
                                @endforeach
                            </select>
                            @error('grade_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email_prive" class="form-label">E-mail privé</label>
                            <input type="email" class="form-control @error('email_prive') is-invalid @enderror"
                                id="email_prive" name="email_prive" value="{{ old('email_prive', $agent->email_prive) }}">
                            @error('email_prive')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email_professionnel" class="form-label">E-mail institutionnel</label>
                            <input type="email" class="form-control @error('email_professionnel') is-invalid @enderror"
                                id="email_professionnel" name="email_professionnel" value="{{ old('email_professionnel', $agent->email_professionnel) }}">
                            @error('email_professionnel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control @error('telephone') is-invalid @enderror"
                                id="telephone" name="telephone" value="{{ old('telephone', $agent->telephone) }}">
                            @error('telephone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="annee_naissance" class="form-label">Année de naissance <span class="text-danger">*</span></label>
                            <input type="number" min="1950" max="2100" class="form-control @error('annee_naissance') is-invalid @enderror"
                                id="annee_naissance" name="annee_naissance" value="{{ old('annee_naissance', $agent->annee_naissance) }}" required>
                            @error('annee_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="date_naissance" class="form-label">Date de naissance (optionnel)</label>
                            <input type="date" class="form-control @error('date_naissance') is-invalid @enderror"
                                id="date_naissance" name="date_naissance" value="{{ old('date_naissance', $agent->date_naissance?->format('Y-m-d')) }}">
                            @error('date_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="lieu_naissance" class="form-label">Lieu de naissance <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('lieu_naissance') is-invalid @enderror"
                                id="lieu_naissance" name="lieu_naissance" value="{{ old('lieu_naissance', $agent->lieu_naissance) }}" required>
                            @error('lieu_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="sexe" class="form-label">Sexe <span class="text-danger">*</span></label>
                            <select class="form-select @error('sexe') is-invalid @enderror" id="sexe" name="sexe" required>
                                <option value="M" @selected(old('sexe', $agent->sexe) === 'M')>Masculin</option>
                                <option value="F" @selected(old('sexe', $agent->sexe) === 'F')>Féminin</option>
                            </select>
                            @error('sexe')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="situation_familiale" class="form-label">État civil</label>
                            <select class="form-select @error('situation_familiale') is-invalid @enderror"
                                id="situation_familiale" name="situation_familiale">
                                <option value="">-- Sélectionner --</option>
                                <option value="célibataire" @selected(old('situation_familiale', $agent->situation_familiale) === 'célibataire')>Célibataire</option>
                                <option value="marié" @selected(old('situation_familiale', $agent->situation_familiale) === 'marié')>Marié(e)</option>
                                <option value="divorcé" @selected(old('situation_familiale', $agent->situation_familiale) === 'divorcé')>Divorcé(e)</option>
                                <option value="veuf" @selected(old('situation_familiale', $agent->situation_familiale) === 'veuf')>Veuf/Veuve</option>
                            </select>
                            @error('situation_familiale')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="nombre_enfants" class="form-label">Nombre d'enfants</label>
                            <input type="number" min="0" class="form-control @error('nombre_enfants') is-invalid @enderror"
                                id="nombre_enfants" name="nombre_enfants" value="{{ old('nombre_enfants', $agent->nombre_enfants) }}">
                            @error('nombre_enfants')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" class="form-control @error('adresse') is-invalid @enderror"
                                id="adresse" name="adresse" value="{{ old('adresse', $agent->adresse) }}">
                            @error('adresse')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Informations professionnelles -->
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-briefcase me-2 text-primary"></i>Informations Professionnelles</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="poste_actuel" class="form-label">Poste Actuel</label>
                            <input type="text" class="form-control @error('poste_actuel') is-invalid @enderror"
                                id="poste_actuel" name="poste_actuel" value="{{ old('poste_actuel', $agent->poste_actuel) }}">
                            @error('poste_actuel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="organe" class="form-label">Organe <span class="text-danger">*</span></label>
                            <select class="form-select @error('organe') is-invalid @enderror" id="organe" name="organe" required>
                                <option value="">-- Sélectionner un organe --</option>
                                @foreach ($organeOptions as $organe)
                                    <option value="{{ $organe }}" @selected(old('organe', $agent->organe) === $organe)>{{ $organe }}</option>
                                @endforeach
                            </select>
                            @error('organe')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- SEN: Rattachement --}}
                        <div class="col-md-6" id="panel-rattachement" style="display:none">
                            <label class="form-label">Rattachement <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <div class="flex-fill">
                                    <input type="radio" class="btn-check" name="type_rattachement" id="ratt_dept" value="departement"
                                           @checked(old('type_rattachement', $agent->departement_id ? 'departement' : '') === 'departement')>
                                    <label class="btn btn-outline-primary btn-sm w-100" for="ratt_dept">
                                        <i class="fas fa-building me-1"></i> Département
                                    </label>
                                </div>
                                <div class="flex-fill">
                                    <input type="radio" class="btn-check" name="type_rattachement" id="ratt_service" value="service_rattache"
                                           @checked(old('type_rattachement', (!$agent->departement_id && $agent->organe === 'Secrétariat Exécutif National') ? 'service_rattache' : '') === 'service_rattache')>
                                    <label class="btn btn-outline-warning btn-sm w-100" for="ratt_service">
                                        <i class="fas fa-link me-1"></i> Service rattaché
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Département (SEN) --}}
                        <div class="col-md-6" id="dept-wrapper" style="display:none">
                            <label for="departement_id" class="form-label">Département</label>
                            <select class="form-select @error('departement_id') is-invalid @enderror"
                                    id="departement_id" name="departement_id">
                                <option value="">-- Sélectionner un département --</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(old('departement_id', $agent->departement_id) == $dept->id)>
                                        {{ $dept->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('departement_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Section (après département) --}}
                        <div class="col-md-6" id="section-wrapper" style="display:none">
                            <label for="section_id" class="form-label">Section</label>
                            <select class="form-select @error('section_id') is-invalid @enderror"
                                    id="section_id" name="section_id">
                                <option value="">-- Aucune (niveau département) --</option>
                                @foreach ($sections->where('type', 'section') as $section)
                                    <option value="{{ $section->id }}" data-dept="{{ $section->department_id }}"
                                        @selected(old('section_id', $agent->section_id ?? '') == $section->id)>
                                        {{ $section->nom }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Laissez vide pour Directeur, Chef de département, Assistant ou Secrétaire.</small>
                            @error('section_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Province (SEP/SEL) --}}
                        <div class="col-md-6" id="province-wrapper" style="display:none">
                            <label for="province_id" class="form-label">Province</label>
                            <select class="form-select @error('province_id') is-invalid @enderror"
                                id="province_id" name="province_id">
                                <option value="">-- Sélectionner une province --</option>
                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}" @selected(old('province_id', $agent->province_id) == $province->id)>
                                        {{ $province->nom_province ?? $province->nom ?? "Province {$province->id}" }}
                                    </option>
                                @endforeach
                            </select>
                            @error('province_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Fonction (filtrée par contexte) --}}
                        <div class="col-md-6">
                            <label for="fonction" class="form-label">Fonction / Poste <span class="text-danger">*</span></label>
                            <select class="form-select @error('fonction') is-invalid @enderror" id="fonction" name="fonction" required>
                                <option value="">-- Sélectionner une fonction --</option>
                                @foreach ($fonctionsAll as $fonction)
                                    <option value="{{ $fonction->nom }}"
                                        data-niveau="{{ $fonction->niveau_administratif }}"
                                        data-type="{{ $fonction->type_poste }}"
                                        @selected(old('fonction', $agent->fonction) === $fonction->nom)>
                                        {{ $fonction->nom }}@if($fonction->est_chef) ★@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('fonction')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="niveau_etudes" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                            <select class="form-select @error('niveau_etudes') is-invalid @enderror"
                                    id="niveau_etudes" name="niveau_etudes" required>
                                <option value="">-- Sélectionner --</option>
                                @foreach(\App\Models\Agent::NIVEAUX_ETUDES as $niveau)
                                    <option value="{{ $niveau }}" {{ old('niveau_etudes', $agent->niveau_etudes) == $niveau ? 'selected' : '' }}>{{ $niveau }}</option>
                                @endforeach
                            </select>
                            @error('niveau_etudes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="domaine_etudes" class="form-label">Domaine d'études</label>
                            <input type="text" class="form-control @error('domaine_etudes') is-invalid @enderror"
                                id="domaine_etudes" name="domaine_etudes"
                                value="{{ old('domaine_etudes', $agent->domaine_etudes) }}"
                                placeholder="Ex: Sciences informatiques, Droit, Médecine...">
                            @error('domaine_etudes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="annee_engagement_programme" class="form-label">Année d'engagement au programme <span class="text-danger">*</span></label>
                            <input type="number" min="1950" max="2100" class="form-control @error('annee_engagement_programme') is-invalid @enderror"
                                id="annee_engagement_programme" name="annee_engagement_programme" value="{{ old('annee_engagement_programme', $agent->annee_engagement_programme) }}" required>
                            @error('annee_engagement_programme')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="date_embauche" class="form-label">Date d'embauche (optionnel)</label>
                            <input type="date" class="form-control @error('date_embauche') is-invalid @enderror"
                                id="date_embauche" name="date_embauche" value="{{ old('date_embauche', $agent->date_embauche?->format('Y-m-d')) }}">
                            @error('date_embauche')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select @error('statut') is-invalid @enderror"
                                id="statut" name="statut" required>
                                <option value="actif" @selected(old('statut', $agent->statut) === 'actif')>Actif</option>
                                <option value="suspendu" @selected(old('statut', $agent->statut) === 'suspendu')>Suspendu</option>
                                <option value="ancien" @selected(old('statut', $agent->statut) === 'ancien')>Ancien</option>
                            </select>
                            @error('statut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Boutons d'action -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('rh.agents.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const organeSelect   = document.getElementById('organe');
    const fonctionSelect = document.getElementById('fonction');
    const panelRatt      = document.getElementById('panel-rattachement');
    const deptWrapper    = document.getElementById('dept-wrapper');
    const sectionWrapper = document.getElementById('section-wrapper');
    const provinceWrapper= document.getElementById('province-wrapper');
    const deptSelect     = document.getElementById('departement_id');
    const sectionSelect  = document.getElementById('section_id');
    const rattRadios     = document.querySelectorAll('[name="type_rattachement"]');

    const organeToNiveau = {
        'secrétariat exécutif national': 'SEN',
        'secrétariat exécutif provincial': 'SEP',
        'secrétariat exécutif local': 'SEL',
    };

    function getNiveau() {
        return organeToNiveau[(organeSelect.value || '').trim().toLowerCase()] || '';
    }

    function syncPanels() {
        const niveau = getNiveau();
        panelRatt.style.display = niveau === 'SEN' ? '' : 'none';
        provinceWrapper.style.display = (niveau === 'SEP' || niveau === 'SEL') ? '' : 'none';

        if (niveau === 'SEN') {
            const ratt = document.querySelector('[name="type_rattachement"]:checked');
            deptWrapper.style.display = (ratt && ratt.value === 'departement') ? '' : 'none';
            sectionWrapper.style.display = 'none';
            if (!ratt || ratt.value !== 'departement') {
                deptSelect.value = '';
                sectionSelect.value = '';
            }
        } else {
            deptWrapper.style.display = 'none';
            sectionWrapper.style.display = 'none';
        }
        filterFonctions();
    }

    function syncSection() {
        if (getNiveau() !== 'SEN') return;
        const deptId = deptSelect.value;
        sectionWrapper.style.display = deptId ? '' : 'none';
        if (!deptId) { sectionSelect.value = ''; filterFonctions(); return; }
        sectionSelect.querySelectorAll('option[data-dept]').forEach(opt => {
            const match = opt.getAttribute('data-dept') === deptId;
            opt.style.display = match ? '' : 'none';
            opt.disabled = !match;
        });
        const sel = sectionSelect.querySelector('option:checked');
        if (sel && sel.disabled) sectionSelect.value = '';
        filterFonctions();
    }

    function filterFonctions() {
        const niveau = getNiveau();
        if (!niveau) return;

        let typePoste = null;
        if (niveau === 'SEN') {
            const ratt = document.querySelector('[name="type_rattachement"]:checked');
            if (ratt && ratt.value === 'service_rattache') {
                typePoste = 'service_rattache';
            } else if (ratt && ratt.value === 'departement') {
                typePoste = sectionSelect.value ? 'section' : 'département';
            }
        } else if (niveau === 'SEP') {
            typePoste = 'province';
        } else if (niveau === 'SEL') {
            typePoste = 'local';
        }

        fonctionSelect.querySelectorAll('option[data-niveau]').forEach(opt => {
            const optNa = opt.getAttribute('data-niveau');
            const optType = opt.getAttribute('data-type');
            let visible = (optNa === niveau || optNa === 'TOUS');
            if (visible && typePoste) {
                visible = (optType === typePoste || optType === 'appui');
            }
            opt.style.display = visible ? '' : 'none';
            opt.disabled = !visible;
        });
        const sel = fonctionSelect.querySelector('option:checked');
        if (sel && sel.disabled) fonctionSelect.value = '';
    }

    organeSelect.addEventListener('change', syncPanels);
    rattRadios.forEach(r => r.addEventListener('change', syncPanels));
    deptSelect.addEventListener('change', syncSection);
    sectionSelect.addEventListener('change', filterFonctions);

    syncPanels();
    syncSection();
});
</script>
@endsection
