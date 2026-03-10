@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-user-plus me-2"></i> Ajouter un Agent</h2>
            <p class="text-muted mb-0">Enregistrez un nouveau agent dans le système</p>
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

            <form action="{{ route('rh.agents.store') }}" method="POST">
                @csrf

                <!-- Informations personnelles -->
                <section class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-user me-2 text-primary"></i>Informations Personnelles</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="matricule_pnmls" class="form-label">Matricule PNMLS <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('matricule_pnmls') is-invalid @enderror"
                                id="matricule_pnmls" name="matricule_pnmls" value="{{ old('matricule_pnmls') }}" required>
                            @error('matricule_pnmls')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('prenom') is-invalid @enderror"
                                id="prenom" name="prenom" value="{{ old('prenom') }}" required>
                            @error('prenom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nom') is-invalid @enderror"
                                id="nom" name="nom" value="{{ old('nom') }}" required>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="date_naissance" class="form-label">Date de Naissance <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_naissance') is-invalid @enderror"
                                id="date_naissance" name="date_naissance" value="{{ old('date_naissance') }}" required>
                            @error('date_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="lieu_naissance" class="form-label">Lieu de Naissance <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('lieu_naissance') is-invalid @enderror"
                                id="lieu_naissance" name="lieu_naissance" value="{{ old('lieu_naissance') }}" required>
                            @error('lieu_naissance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control @error('telephone') is-invalid @enderror"
                                id="telephone" name="telephone" value="{{ old('telephone') }}">
                            @error('telephone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" class="form-control @error('adresse') is-invalid @enderror"
                                id="adresse" name="adresse" value="{{ old('adresse') }}">
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
                            <label for="date_embauche" class="form-label">Date d'Embauche <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_embauche') is-invalid @enderror"
                                id="date_embauche" name="date_embauche" value="{{ old('date_embauche') }}" required>
                            @error('date_embauche')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="poste_actuel" class="form-label">Poste Actuel</label>
                            <input type="text" class="form-control @error('poste_actuel') is-invalid @enderror"
                                id="poste_actuel" name="poste_actuel" value="{{ old('poste_actuel') }}">
                            @error('poste_actuel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="province_id" class="form-label">Province</label>
                            <select class="form-select @error('province_id') is-invalid @enderror"
                                id="province_id" name="province_id">
                                <option value="">-- Sélectionner une province --</option>
                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}" @selected(old('province_id') == $province->id)>
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
                                    <option value="{{ $dept->id }}" @selected(old('departement_id') == $dept->id)>
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

                <!-- Boutons d'action -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Créer l'agent
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
