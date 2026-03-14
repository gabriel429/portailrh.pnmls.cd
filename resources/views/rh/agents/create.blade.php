@extends('layouts.app')

@php
/** @var \Illuminate\Support\Collection $roles */
/** @var \Illuminate\Support\Collection $departments */
/** @var \Illuminate\Support\Collection $provinces */
/** @var array $organeOptions */
/** @var array $fonctionOptions */
/** @var \Illuminate\Support\Collection $grades */
@endphp

@section('title', 'Nouvel Agent – Portail RH PNMLS')

@section('content')
<div class="container-fluid py-5">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-user-plus me-2 text-primary"></i> Nouvel Agent</h2>
            <p class="text-muted mb-0">Enregistrer un nouvel agent au Portail RH PNMLS</p>
        </div>
        <a href="{{ route('rh.agents.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Retour
        </a>
    </div>

    {{-- Formulaire --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreurs de validation&nbsp;:</strong>
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

                {{-- ── Informations personnelles ── --}}
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-user me-2 text-primary"></i>Informations Personnelles</h5>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="matricule_pnmls" class="form-label">Matricule PNMLS <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('matricule_pnmls') is-invalid @enderror"
                                   id="matricule_pnmls" name="matricule_pnmls"
                                   value="{{ old('matricule_pnmls') }}" required>
                            @error('matricule_pnmls')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="matricule_etat" class="form-label">Matricule de l'État <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('matricule_etat') is-invalid @enderror"
                                   id="matricule_etat" name="matricule_etat"
                                   value="{{ old('matricule_etat') }}" required>
                            @error('matricule_etat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="provenance_matricule" class="form-label">Provenance matricule <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('provenance_matricule') is-invalid @enderror"
                                   id="provenance_matricule" name="provenance_matricule"
                                   value="{{ old('provenance_matricule') }}" required>
                            @error('provenance_matricule')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('prenom') is-invalid @enderror"
                                   id="prenom" name="prenom"
                                   value="{{ old('prenom') }}" required>
                            @error('prenom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nom') is-invalid @enderror"
                                   id="nom" name="nom"
                                   value="{{ old('nom') }}" required>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="postnom" class="form-label">Post-nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('postnom') is-invalid @enderror"
                                   id="postnom" name="postnom"
                                   value="{{ old('postnom') }}" required>
                            @error('postnom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">E-mail institutionnel <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email"
                                   value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email_prive" class="form-label">E-mail privé</label>
                            <input type="email" class="form-control @error('email_prive') is-invalid @enderror"
                                   id="email_prive" name="email_prive"
                                   value="{{ old('email_prive') }}">
                            @error('email_prive')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email_professionnel" class="form-label">E-mail professionnel</label>
                            <input type="email" class="form-control @error('email_professionnel') is-invalid @enderror"
                                   id="email_professionnel" name="email_professionnel"
                                   value="{{ old('email_professionnel') }}">
                            @error('email_professionnel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control @error('telephone') is-invalid @enderror"
                                   id="telephone" name="telephone"
                                   value="{{ old('telephone') }}">
                            @error('telephone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="sexe" class="form-label">Sexe <span class="text-danger">*</span></label>
                            <select class="form-select @error('sexe') is-invalid @enderror"
                                    id="sexe" name="sexe" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                                <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                            </select>
                            @error('sexe')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="annee_naissance" class="form-label">Année de naissance <span class="text-danger">*</span></label>
                            <input type="number" min="1950" max="2100"
                                   class="form-control @error('annee_naissance') is-invalid @enderror"
                                   id="annee_naissance" name="annee_naissance"
                                   value="{{ old('annee_naissance') }}" required>
                            @error('annee_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="date_naissance" class="form-label">Date de naissance <small class="text-muted">(optionnel)</small></label>
                            <input type="date" class="form-control @error('date_naissance') is-invalid @enderror"
                                   id="date_naissance" name="date_naissance"
                                   value="{{ old('date_naissance') }}">
                            @error('date_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="lieu_naissance" class="form-label">Lieu de naissance <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('lieu_naissance') is-invalid @enderror"
                                   id="lieu_naissance" name="lieu_naissance"
                                   value="{{ old('lieu_naissance') }}" required>
                            @error('lieu_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" class="form-control @error('adresse') is-invalid @enderror"
                                   id="adresse" name="adresse"
                                   value="{{ old('adresse') }}">
                            @error('adresse')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </section>

                <hr class="my-4">

                {{-- ── Informations professionnelles ── --}}
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-briefcase me-2 text-primary"></i>Informations Professionnelles</h5>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="organe" class="form-label">Organe <span class="text-danger">*</span></label>
                            <select class="form-select @error('organe') is-invalid @enderror"
                                    id="organe" name="organe" required>
                                <option value="">-- Sélectionner un organe --</option>
                                @foreach ($organeOptions as $organe)
                                    <option value="{{ $organe }}" @selected(old('organe') === $organe)>{{ $organe }}</option>
                                @endforeach
                            </select>
                            @error('organe')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="fonction" class="form-label">Fonction <span class="text-danger">*</span></label>
                            <select class="form-select @error('fonction') is-invalid @enderror"
                                    id="fonction" name="fonction" required>
                                <option value="">-- Sélectionner une fonction --</option>
                                @foreach ($fonctionOptions as $groupe => $fonctions)
                                    <optgroup label="{{ $groupe }}">
                                        @foreach ($fonctions as $fonction)
                                            <option value="{{ $fonction->nom }}" @selected(old('fonction') === $fonction->nom)>
                                                {{ $fonction->nom }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('fonction')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="grade_id" class="form-label">Grade de l'État <span class="text-danger">*</span></label>
                            <select class="form-select @error('grade_id') is-invalid @enderror"
                                    id="grade_id" name="grade_id" required>
                                <option value="">-- Sélectionner un grade --</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}" @selected(old('grade_id') == $grade->id)>
                                        {{ $grade->libelle }} ({{ $grade->nom_categorie }})
                                    </option>
                                @endforeach
                            </select>
                            @error('grade_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="niveau_etudes" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('niveau_etudes') is-invalid @enderror"
                                   id="niveau_etudes" name="niveau_etudes"
                                   value="{{ old('niveau_etudes') }}" required>
                            @error('niveau_etudes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="annee_engagement_programme" class="form-label">Année d'engagement au programme <span class="text-danger">*</span></label>
                            <input type="number" min="1950" max="2100"
                                   class="form-control @error('annee_engagement_programme') is-invalid @enderror"
                                   id="annee_engagement_programme" name="annee_engagement_programme"
                                   value="{{ old('annee_engagement_programme') }}" required>
                            @error('annee_engagement_programme')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="date_embauche" class="form-label">Date d'embauche <small class="text-muted">(optionnel)</small></label>
                            <input type="date" class="form-control @error('date_embauche') is-invalid @enderror"
                                   id="date_embauche" name="date_embauche"
                                   value="{{ old('date_embauche') }}">
                            @error('date_embauche')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6" id="province-wrapper">
                            <label for="province_id" class="form-label">Province</label>
                            <select class="form-select @error('province_id') is-invalid @enderror"
                                    id="province_id" name="province_id">
                                <option value="">-- Sélectionner une province --</option>
                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}" @selected(old('province_id') == $province->id)>
                                        {{ $province->nom_province ?? $province->nom ?? "Province {$province->id}" }}
                                    </option>
                                @endforeach
                            </select>
                            @error('province_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="departement_id" class="form-label">Département</label>
                            <select class="form-select @error('departement_id') is-invalid @enderror"
                                    id="departement_id" name="departement_id">
                                <option value="">-- Sélectionner un département --</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(old('departement_id') == $dept->id)>
                                        {{ $dept->nom_dept ?? $dept->nom ?? "Département {$dept->id}" }}
                                    </option>
                                @endforeach
                            </select>
                            @error('departement_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="role_id" class="form-label">Rôle</label>
                            <select class="form-select @error('role_id') is-invalid @enderror"
                                    id="role_id" name="role_id">
                                <option value="">-- Sélectionner un rôle --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                        {{ $role->nom_role }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </section>

                <hr class="my-4">

                {{-- ── Boutons d'action ── --}}
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i> Enregistrer l'agent
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
    const organeSelect      = document.getElementById('organe');
    const departementSelect = document.getElementById('departement_id');
    const provinceWrapper   = document.getElementById('province-wrapper');
    const provinceSelect    = document.getElementById('province_id');

    const isNational = (v) => (v || '').trim().toLowerCase() === 'secrétariat exécutif national';
    const isProvLocal = (v) => {
        const n = (v || '').trim().toLowerCase();
        return n === 'secrétariat exécutif provincial' || n === 'secrétariat exécutif local';
    };

    const syncFields = () => {
        if (isNational(organeSelect.value)) {
            provinceWrapper.style.display = 'none';
            provinceSelect.value    = '';
            provinceSelect.disabled = true;
        } else {
            provinceWrapper.style.display = '';
            provinceSelect.disabled = false;
        }
        const disableDept = isProvLocal(organeSelect.value);
        departementSelect.disabled = disableDept;
        if (disableDept) departementSelect.value = '';
    };

    organeSelect.addEventListener('change', syncFields);
    syncFields();
});
</script>
@endsection
