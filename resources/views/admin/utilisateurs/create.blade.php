@extends('admin.layouts.sidebar')

@section('title', 'Créer Utilisateur')
@section('page-title', 'Créer Compte pour un Agent')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="form-card">
            <h5 class="mb-4">
                <i class="fas fa-user-plus me-2 text-primary"></i>
                Créer un Compte Utilisateur
            </h5>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <strong>Erreurs de validation:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('admin.utilisateurs.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="agent_id" class="form-label">Agent <span class="text-danger">*</span></label>
                    <select class="form-select @error('agent_id') is-invalid @enderror"
                            id="agent_id" name="agent_id" required>
                        <option value="">-- Sélectionner un agent --</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" @selected(old('agent_id') == $agent->id)
                                    data-email="{{ $agent->email_professionnel }}">
                                {{ $agent->nom }} {{ $agent->prenom }} ({{ $agent->email_professionnel }})
                            </option>
                        @endforeach
                    </select>
                    @error('agent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="role_id" class="form-label">Rôle <span class="text-muted">(optionnel)</span></label>
                    <select class="form-select @error('role_id') is-invalid @enderror"
                            id="role_id" name="role_id">
                        <option value="">-- Aucun rôle --</option>
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

                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Minimum 8 caractères</small>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                           id="password_confirmation" name="password_confirmation" required>
                    @error('password_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Créer le Compte
                    </button>
                    <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card bg-light">
            <h6 class="mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Informations
            </h6>
            <small class="text-muted">
                <p><strong>Agent:</strong> L'utilisateur sera lié à un agent existant</p>
                <p><strong>Email:</strong> Utilisera automatiquement l'email professionnel de l'agent</p>
                <p><strong>Mot de passe:</strong> Défini par l'administrateur pour accéder à l'application</p>
                <p><strong>Rôle:</strong> Optionnel. Peut être attribué gratuitement après création.</p>
            </small>
        </div>
    </div>
</div>
@endsection
