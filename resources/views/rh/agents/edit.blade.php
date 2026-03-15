@extends('layouts.app')

@php
/** @var \Illuminate\Support\Collection $roles */
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
                            <label for="matricule_pnmls" class="form-label">Matricule PNMLS</label>
                            <input type="text" class="form-control" id="matricule_pnmls"
                                value="{{ $agent->matricule_pnmls }}" disabled>
                            <small class="text-muted">Le matricule ne peut pas être modifié</small>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" name="email" value="{{ old('email', $agent->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                id="matricule_etat" name="matricule_etat" value="{{ old('matricule_etat', $agent->matricule_etat ?? 'N.U.') }}" placeholder="Optionnel - N.U. si vide">
                            <small class="text-muted">Laisser vide pour "N.U." (Nouvelle Unité)</small>
                            @error('matricule_etat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="institution_id" class="form-label">Provenance (Institution d'origine)</label>
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
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control @error('telephone') is-invalid @enderror"
                                id="telephone" name="telephone" value="{{ old('telephone', $agent->telephone) }}">
                            @error('telephone')
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
                            <label for="email_professionnel" class="form-label">E-mail professionnel</label>
                            <input type="email" class="form-control @error('email_professionnel') is-invalid @enderror"
                                id="email_professionnel" name="email_professionnel" value="{{ old('email_professionnel', $agent->email_professionnel) }}">
                            @error('email_professionnel')
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

                        <div class="col-md-6">
                            <label for="fonction" class="form-label">Fonction <span class="text-danger">*</span></label>
                            <select class="form-select @error('fonction') is-invalid @enderror" id="fonction" name="fonction" required>
                                <option value="">-- Sélectionner une fonction --</option>
                                @foreach ($fonctionOptions as $groupe => $fonctions)
                                    <optgroup label="{{ $groupe }}">
                                        @foreach ($fonctions as $fonction)
                                            <option value="{{ $fonction->nom }}" @selected(old('fonction', $agent->fonction) === $fonction->nom)>
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
                            <label for="grade_id" class="form-label">Grade de l'État</label>
                            <select class="form-select @error('grade_id') is-invalid @enderror"
                                    id="grade_id" name="grade_id">
                                <option value="">-- Sélectionner un grade (N.U. si vide) --</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}" @selected(old('grade_id', $agent->grade_id) == $grade->id)>
                                        {{ $grade->libelle }} ({{ $grade->nom_categorie }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Optionnel - N.U. si non sélectionné</small>
                            @error('grade_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="niveau_etudes" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('niveau_etudes') is-invalid @enderror"
                                id="niveau_etudes" name="niveau_etudes" value="{{ old('niveau_etudes', $agent->niveau_etudes) }}" required>
                            @error('niveau_etudes')
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

                        <div class="col-md-6" id="province-wrapper">
                            <label for="province_id" class="form-label">Province</label>
                            <select class="form-select @error('province_id') is-invalid @enderror"
                                id="province_id" name="province_id">
                                <option value="">-- Sélectionner une province --</option>
                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}" @selected(old('province_id', $agent->province_id) == $province->id)>
                                        {{ $province->nom_province ?? "Province {$province->id}" }}
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
                                    <option value="{{ $dept->id }}" @selected(old('departement_id', $agent->departement_id) == $dept->id)>
                                        {{ $dept->nom_dept ?? "Département {$dept->id}" }}
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
                                    <option value="{{ $role->id }}" @selected(old('role_id', $agent->role_id) == $role->id)>
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
    const organeInput       = document.getElementById('organe');
    const fonctionSelect    = document.getElementById('fonction');
    const departementSelect = document.getElementById('departement_id');
    const provinceWrapper   = document.getElementById('province-wrapper');
    const provinceSelect    = document.getElementById('province_id');

    // Mapping entre noms d'organe et codes de niveau administratif
    const organeToNiveauMap = {
        'secrétariat exécutif national': 'SEN',
        'secrétariat exécutif provincial': 'SEP',
        'secrétariat exécutif local': 'SEL',
    };

    const isNational = (value) =>
        (value || '').trim().toLowerCase() === 'secrétariat exécutif national';

    const shouldDisableDepartment = (value) => {
        const normalized = (value || '').trim().toLowerCase();
        return normalized === 'secrétariat exécutif provincial' || normalized === 'secrétariat exécutif local';
    };

    // Fonction pour récupérer et afficher les fonctions filtrées
    const updateFonctions = async (organe) => {
        if (!organe) {
            // Si pas d'organe, réinitialiser le dropdown
            fonctionSelect.innerHTML = '<option value="">-- Sélectionner une fonction --</option>';
            return;
        }

        const niveauCode = organeToNiveauMap[(organe || '').trim().toLowerCase()];

        if (!niveauCode) {
            // Organe non reconnu
            fonctionSelect.innerHTML = '<option value="">-- Sélectionner une fonction --</option>';
            return;
        }

        try {
            const response = await fetch(`/admin/api/fonctions-by-organe/${niveauCode}`);
            const fonctions = await response.json();

            // Grouper les fonctions par type_poste
            const grouped = {};
            fonctions.forEach(f => {
                const type = f.type_poste || 'Autres';
                if (!grouped[type]) grouped[type] = [];
                grouped[type].push(f);
            });

            // Construire le HTML
            let html = '<option value="">-- Sélectionner une fonction --</option>';
            for (const [type, items] of Object.entries(grouped)) {
                html += `<optgroup label="${type}">`;
                items.forEach(f => {
                    html += `<option value="${f.nom}">${f.nom}</option>`;
                });
                html += '</optgroup>';
            }

            fonctionSelect.innerHTML = html;
        } catch (error) {
            console.error('Erreur lors du chargement des fonctions:', error);
        }
    };

    const syncFields = () => {
        // Province : masquée si organe national
        if (isNational(organeInput.value)) {
            provinceWrapper.style.display = 'none';
            provinceSelect.value = '';
            provinceSelect.disabled = true;
        } else {
            provinceWrapper.style.display = '';
            provinceSelect.disabled = false;
        }

        // Département : désactivé si provincial ou local
        const disableDept = shouldDisableDepartment(organeInput.value);
        departementSelect.disabled = disableDept;
        if (disableDept) departementSelect.value = '';
    };

    // Écouter les changements d'organe
    organeInput.addEventListener('change', function () {
        updateFonctions(this.value);
        syncFields();
    });

    // Initialiser les fonctions au chargement
    if (organeInput.value) {
        updateFonctions(organeInput.value);
    }

    syncFields();
});
</script>
@endsection
