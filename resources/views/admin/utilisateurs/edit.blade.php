@extends('admin.layouts.sidebar')

@section('title', 'Modifier Utilisateur')
@section('page-title', 'Modifier Utilisateur: ' . $user->agent->nom ?? $user->name)

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="form-card">
            <h5 class="mb-4">
                <i class="fas fa-user-edit me-2 text-primary"></i>
                Modifier l'Utilisateur
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

            <form action="{{ route('admin.utilisateurs.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="agent" class="form-label">Agent</label>
                    <input type="text" class="form-control" id="agent" disabled
                           value="{{ $user->agent?->nom }} {{ $user->agent?->prenom }} ({{ $user->agent?->email_professionnel }})">
                    <small class="form-text text-muted">L'agent ne peut pas être changé</small>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" disabled
                           value="{{ $user->email }}">
                    <small class="form-text text-muted">Email professionnel de l'agent (automatique)</small>
                </div>

                <div class="mb-3">
                    <label for="role_id" class="form-label">Rôle</label>
                    <select class="form-select @error('role_id') is-invalid @enderror"
                            id="role_id" name="role_id">
                        <option value="">-- Aucun rôle --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                                {{ $role->nom_role }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Mot de passe:</strong> Laissez vide pour garder le mot de passe actuel
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Nouveau Mot de passe <span class="text-muted">(optionnel)</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Minimum 8 caractères si modifié</small>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                           id="password_confirmation" name="password_confirmation">
                    @error('password_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Enregistrer les modifications
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
                Détails
            </h6>
            <small class="text-muted">
                <p><strong>ID:</strong> {{ $user->id }}</p>
                <p><strong>Créé:</strong> {{ $user->created_at?->format('d/m/Y H:i') }}</p>
                <p><strong>Modifié:</strong> {{ $user->updated_at?->format('d/m/Y H:i') }}</p>
                @if ($user->id === auth()->id())
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-user-check me-2"></i>
                        C'est votre compte
                    </div>
                @endif
            </small>
        </div>
    </div>
</div>
@endsection
