@extends('layouts.app')

@php
/** @var \Illuminate\Support\Collection $departments */
/** @var \Illuminate\Support\Collection $provinces */
/** @var array $organeOptions */
/** @var array $fonctionOptions */
/** @var \Illuminate\Support\Collection $grades */
/** @var \Illuminate\Support\Collection $institutionCategories */
@endphp

@section('title', 'Nouvel Agent – Portail RH PNMLS')

@section('css')
<style>
    .agent-form-hero {
        background: linear-gradient(135deg, #0077B5 0%, #005885 100%);
        color: #fff;
        border-radius: 16px;
        padding: 2rem 2.5rem;
        margin-bottom: 2rem;
    }
    .agent-form-hero h2 { font-weight: 700; margin-bottom: .25rem; }
    .agent-form-hero p { opacity: .85; margin-bottom: 0; }
    .form-section {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        padding: 2rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
    }
    .form-section-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1.5rem;
        padding-bottom: .75rem;
        border-bottom: 2px solid #e9ecef;
    }
    .form-section-icon {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: #fff; flex-shrink: 0;
    }
    .form-section-header h5 { font-weight: 700; margin-bottom: 0; color: #1a1a2e; }
    .form-section-header small { color: #6c757d; font-weight: 400; }
    .form-label { font-weight: 600; color: #344054; font-size: .875rem; margin-bottom: .35rem; }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1.5px solid #d0d5dd;
        padding: .55rem .85rem;
        transition: border-color .2s, box-shadow .2s;
    }
    .form-control:focus, .form-select:focus {
        border-color: #0077B5;
        box-shadow: 0 0 0 3px rgba(0,119,181,.12);
    }
    .btn-submit {
        background: linear-gradient(135deg, #0077B5, #005885);
        border: none; color: #fff; font-weight: 600;
        padding: .7rem 2rem; border-radius: 10px;
        transition: transform .15s, box-shadow .15s;
    }
    .btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(0,119,181,.35);
        color: #fff;
    }
    .btn-cancel {
        background: #f2f4f7; color: #344054; font-weight: 600;
        border: 1.5px solid #d0d5dd; border-radius: 10px;
        padding: .7rem 2rem;
    }
    .btn-cancel:hover { background: #e4e7ec; color: #1a1a2e; }
    .step-badge {
        background: #0077B5; color: #fff; font-weight: 700;
        width: 24px; height: 24px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .75rem; margin-right: .35rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid" style="max-width: 960px; padding-top: 2rem; padding-bottom: 3rem;">

    {{-- Hero --}}
    <div class="agent-form-hero">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-user-plus me-2"></i> Nouvel Agent</h2>
                <p>Enregistrer un nouvel agent au Portail RH PNMLS</p>
            </div>
            <a href="{{ route('rh.agents.index') }}" class="btn btn-light btn-sm" style="border-radius:8px; font-weight:600;">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" style="border-radius:10px">
            <strong><i class="fas fa-exclamation-triangle me-1"></i> Erreurs de validation :</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('rh.agents.store') }}" method="POST">
        @csrf

        {{-- ══ Section 1 : Identité ══ --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="form-section-icon" style="background:linear-gradient(135deg,#0077B5,#00a0dc)">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <h5>Identité de l'agent</h5>
                    <small>Nom, prénom, post-nom et état civil</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nom') is-invalid @enderror"
                           id="nom" name="nom" value="{{ old('nom') }}" placeholder="KABONGO" required>
                    @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="postnom" class="form-label">Post-nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('postnom') is-invalid @enderror"
                           id="postnom" name="postnom" value="{{ old('postnom') }}" placeholder="MWAMBA" required>
                    @error('postnom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('prenom') is-invalid @enderror"
                           id="prenom" name="prenom" value="{{ old('prenom') }}" placeholder="Jean" required>
                    @error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label for="sexe" class="form-label">Sexe <span class="text-danger">*</span></label>
                    <select class="form-select @error('sexe') is-invalid @enderror" id="sexe" name="sexe" required>
                        <option value="">--</option>
                        <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                        <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                    </select>
                    @error('sexe')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="annee_naissance" class="form-label">Année de naissance <span class="text-danger">*</span></label>
                    <input type="number" min="1950" max="2100" class="form-control @error('annee_naissance') is-invalid @enderror"
                           id="annee_naissance" name="annee_naissance" value="{{ old('annee_naissance') }}" placeholder="1985" required>
                    @error('annee_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="date_naissance" class="form-label">Date de naissance</label>
                    <input type="date" class="form-control @error('date_naissance') is-invalid @enderror"
                           id="date_naissance" name="date_naissance" value="{{ old('date_naissance') }}">
                    @error('date_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="lieu_naissance" class="form-label">Lieu de naissance <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('lieu_naissance') is-invalid @enderror"
                           id="lieu_naissance" name="lieu_naissance" value="{{ old('lieu_naissance') }}" placeholder="Kinshasa" required>
                    @error('lieu_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ══ Section 2 : Coordonnées ══ --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="form-section-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="fas fa-address-book"></i>
                </div>
                <div>
                    <h5>Coordonnées</h5>
                    <small>E-mails, téléphone et adresse</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="email_professionnel" class="form-label"><i class="fas fa-envelope text-primary me-1"></i> E-mail institutionnel</label>
                    <input type="email" class="form-control @error('email_professionnel') is-invalid @enderror"
                           id="email_professionnel" name="email_professionnel" value="{{ old('email_professionnel') }}" placeholder="nom@pnmls.cd">
                    @error('email_professionnel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="email_prive" class="form-label"><i class="fas fa-envelope-open text-muted me-1"></i> E-mail privé</label>
                    <input type="email" class="form-control @error('email_prive') is-invalid @enderror"
                           id="email_prive" name="email_prive" value="{{ old('email_prive') }}" placeholder="nom@gmail.com">
                    @error('email_prive')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="telephone" class="form-label"><i class="fas fa-phone text-success me-1"></i> Téléphone</label>
                    <input type="tel" class="form-control @error('telephone') is-invalid @enderror"
                           id="telephone" name="telephone" value="{{ old('telephone') }}" placeholder="+243 ...">
                    @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="adresse" class="form-label"><i class="fas fa-map-marker-alt text-danger me-1"></i> Adresse</label>
                    <input type="text" class="form-control @error('adresse') is-invalid @enderror"
                           id="adresse" name="adresse" value="{{ old('adresse') }}" placeholder="Av. ..., Commune ...">
                    @error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ══ Section 3 : Matricule & Grade ══ --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="form-section-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                    <i class="fas fa-id-badge"></i>
                </div>
                <div>
                    <h5>Matricule & Grade</h5>
                    <small>Matricule de l'État, institution d'origine et grade</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="matricule_etat" class="form-label">Matricule de l'État</label>
                    <input type="text" class="form-control @error('matricule_etat') is-invalid @enderror"
                           id="matricule_etat" name="matricule_etat" value="{{ old('matricule_etat') }}" placeholder="N.U. si vide">
                    <small class="text-muted">Laisser vide = "N.U."</small>
                    @error('matricule_etat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="institution_id" class="form-label">Institution d'origine</label>
                    <select class="form-select @error('institution_id') is-invalid @enderror" id="institution_id" name="institution_id">
                        <option value="">-- Sélectionner --</option>
                        @foreach ($institutionCategories as $category)
                            <optgroup label="{{ $category->nom }}">
                                @foreach ($category->institutions as $institution)
                                    <option value="{{ $institution->id }}" @selected(old('institution_id') == $institution->id)>{{ $institution->nom }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('institution_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="grade_id" class="form-label">Grade de l'État</label>
                    <select class="form-select @error('grade_id') is-invalid @enderror" id="grade_id" name="grade_id">
                        <option value="">-- Sélectionner --</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" @selected(old('grade_id') == $grade->id)>{{ $grade->libelle }} ({{ $grade->categorie }})</option>
                        @endforeach
                    </select>
                    @error('grade_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ══ Section 4 : Affectation ══ --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="form-section-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div>
                    <h5>Affectation & Fonction</h5>
                    <small>Organe, département/service, section et fonction</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="organe" class="form-label"><span class="step-badge">1</span> Organe <span class="text-danger">*</span></label>
                    <select class="form-select @error('organe') is-invalid @enderror" id="organe" name="organe" required>
                        <option value="">-- Sélectionner un organe --</option>
                        @foreach ($organeOptions as $organe)
                            <option value="{{ $organe }}" @selected(old('organe') === $organe)>{{ $organe }}</option>
                        @endforeach
                    </select>
                    @error('organe')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- SEN: Rattachement --}}
                <div class="col-md-6" id="panel-rattachement" style="display:none">
                    <label class="form-label"><span class="step-badge">2</span> Rattachement</label>
                    <div class="d-flex gap-2">
                        <div class="flex-fill">
                            <input type="radio" class="btn-check" name="type_rattachement" id="ratt_dept" value="departement"
                                   @checked(old('type_rattachement', old('departement_id') ? 'departement' : '') === 'departement')>
                            <label class="btn btn-outline-primary w-100" for="ratt_dept" style="border-radius:8px">
                                <i class="fas fa-building me-1"></i> Département
                            </label>
                        </div>
                        <div class="flex-fill">
                            <input type="radio" class="btn-check" name="type_rattachement" id="ratt_service" value="service_rattache"
                                   @checked(old('type_rattachement') === 'service_rattache')>
                            <label class="btn btn-outline-warning w-100" for="ratt_service" style="border-radius:8px">
                                <i class="fas fa-link me-1"></i> Service rattaché
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Département (SEN) --}}
                <div class="col-md-6" id="dept-wrapper" style="display:none">
                    <label for="departement_id" class="form-label"><span class="step-badge">3</span> Département</label>
                    <select class="form-select @error('departement_id') is-invalid @enderror" id="departement_id" name="departement_id">
                        <option value="">-- Sélectionner un département --</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('departement_id') == $dept->id)>{{ $dept->nom }}</option>
                        @endforeach
                    </select>
                    @error('departement_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Section (après département) --}}
                <div class="col-md-6" id="section-wrapper" style="display:none">
                    <label for="section_id" class="form-label"><span class="step-badge">4</span> Section</label>
                    <select class="form-select @error('section_id') is-invalid @enderror" id="section_id" name="section_id">
                        <option value="">-- Aucune (niveau département) --</option>
                        @foreach ($sections->where('type', 'section') as $section)
                            <option value="{{ $section->id }}" data-dept="{{ $section->department_id }}" @selected(old('section_id') == $section->id)>{{ $section->nom }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Laissez vide pour Directeur, Chef, Assistant ou Secrétaire du département.</small>
                    @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Province (SEP/SEL) --}}
                <div class="col-md-6" id="province-wrapper" style="display:none">
                    <label for="province_id" class="form-label"><span class="step-badge">2</span> Province</label>
                    <select class="form-select @error('province_id') is-invalid @enderror" id="province_id" name="province_id">
                        <option value="">-- Sélectionner une province --</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" @selected(old('province_id') == $province->id)>{{ $province->nom_province ?? $province->nom ?? "Province {$province->id}" }}</option>
                        @endforeach
                    </select>
                    @error('province_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Fonction --}}
                <div class="col-md-6">
                    <label for="fonction" class="form-label"><i class="fas fa-briefcase text-purple me-1"></i> Fonction / Poste <span class="text-danger">*</span></label>
                    <select class="form-select @error('fonction') is-invalid @enderror" id="fonction" name="fonction" required>
                        <option value="">-- Sélectionner une fonction --</option>
                        @foreach ($fonctionsAll as $fonction)
                            <option value="{{ $fonction->nom }}" data-niveau="{{ $fonction->niveau_administratif }}" data-type="{{ $fonction->type_poste }}"
                                @selected(old('fonction') === $fonction->nom)>{{ $fonction->nom }}@if($fonction->est_chef) ★@endif</option>
                        @endforeach
                    </select>
                    @error('fonction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ══ Section 5 : Formation & Engagement ══ --}}
        <div class="form-section">
            <div class="form-section-header">
                <div class="form-section-icon" style="background:linear-gradient(135deg,#ec4899,#be185d)">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div>
                    <h5>Formation & Engagement</h5>
                    <small>Niveau d'études, domaine et dates d'engagement</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="niveau_etudes" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                    <select class="form-select @error('niveau_etudes') is-invalid @enderror" id="niveau_etudes" name="niveau_etudes" required>
                        <option value="">-- Sélectionner --</option>
                        @foreach(\App\Models\Agent::NIVEAUX_ETUDES as $niveau)
                            <option value="{{ $niveau }}" {{ old('niveau_etudes') == $niveau ? 'selected' : '' }}>{{ $niveau }}</option>
                        @endforeach
                    </select>
                    @error('niveau_etudes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="domaine_etudes" class="form-label">Domaine d'études</label>
                    <input type="text" class="form-control @error('domaine_etudes') is-invalid @enderror"
                           id="domaine_etudes" name="domaine_etudes" value="{{ old('domaine_etudes') }}" placeholder="Ex: Sciences informatiques, Droit...">
                    @error('domaine_etudes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="annee_engagement_programme" class="form-label">Année d'engagement au programme <span class="text-danger">*</span></label>
                    <input type="number" min="1950" max="2100" class="form-control @error('annee_engagement_programme') is-invalid @enderror"
                           id="annee_engagement_programme" name="annee_engagement_programme" value="{{ old('annee_engagement_programme') }}" placeholder="2020" required>
                    @error('annee_engagement_programme')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="date_embauche" class="form-label">Date d'embauche <small class="text-muted">(optionnel)</small></label>
                    <input type="date" class="form-control @error('date_embauche') is-invalid @enderror"
                           id="date_embauche" name="date_embauche" value="{{ old('date_embauche') }}">
                    @error('date_embauche')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ══ Boutons d'action ══ --}}
        <div class="d-flex gap-3 justify-content-end">
            <a href="{{ route('rh.agents.index') }}" class="btn btn-cancel">
                <i class="fas fa-times me-1"></i> Annuler
            </a>
            <button type="submit" class="btn btn-submit">
                <i class="fas fa-user-plus me-1"></i> Enregistrer l'agent
            </button>
        </div>

    </form>
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
            deptSelect.value = '';
            sectionSelect.value = '';
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
