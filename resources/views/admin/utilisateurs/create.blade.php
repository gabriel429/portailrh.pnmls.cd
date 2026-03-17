@extends('admin.layouts.sidebar')

@section('title', 'Créer Utilisateur')
@section('page-title', 'Créer Compte pour un Agent')

@section('topbar-actions')
<a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="form-card" style="max-width:680px">
    <div class="form-section-title"><i class="fas fa-user-plus me-2"></i>Nouveau compte utilisateur</div>

    <form action="{{ route('admin.utilisateurs.store') }}" method="POST">
        @csrf

        <div class="row g-3">
            <div class="col-12">
                <label for="agent_id" class="form-label fw-semibold">Agent <span class="text-danger">*</span></label>
                <select class="form-select @error('agent_id') is-invalid @enderror"
                        id="agent_id" name="agent_id" required>
                    <option value="">– Sélectionner un agent –</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected(old('agent_id') == $agent->id)
                                data-email="{{ $agent->email_professionnel }}">
                            {{ $agent->nom }} {{ $agent->prenom }} ({{ $agent->email_professionnel }})
                        </option>
                    @endforeach
                </select>
                @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">L'email professionnel de l'agent sera utilisé comme identifiant</small>
            </div>

            <div class="col-12">
                <label for="role_id" class="form-label fw-semibold">Rôle <span class="text-muted">(optionnel)</span></label>
                <select class="form-select @error('role_id') is-invalid @enderror"
                        id="role_id" name="role_id">
                    <option value="">– Aucun rôle –</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                            {{ $role->nom_role }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12"><div class="form-section-title"><i class="fas fa-lock me-2"></i>Mot de passe</div></div>

            <div class="col-md-6">
                <label for="password" class="form-label fw-semibold">Mot de passe <span class="text-danger">*</span></label>
                <input type="password" class="form-control @error('password') is-invalid @enderror"
                       id="password" name="password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">Minimum 8 caractères</small>
            </div>

            <div class="col-md-6">
                <label for="password_confirmation" class="form-label fw-semibold">Confirmer <span class="text-danger">*</span></label>
                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                       id="password_confirmation" name="password_confirmation" required>
                @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Créer le Compte
                </button>
                <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </form>
</div>
@endsection
