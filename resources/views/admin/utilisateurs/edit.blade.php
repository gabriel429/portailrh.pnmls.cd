@extends('admin.layouts.sidebar')

@section('title', 'Modifier Utilisateur')
@section('page-title', 'Modifier : ' . ($user->agent?->prenom ?? '') . ' ' . ($user->agent?->nom ?? $user->name))

@section('topbar-actions')
<a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Retour
</a>
@endsection

@section('content')
<div class="form-card" style="max-width:680px">
    <div class="form-section-title"><i class="fas fa-user-edit me-2"></i>Informations du compte</div>

    <form action="{{ route('admin.utilisateurs.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Agent</label>
                <input type="text" class="form-control" disabled
                       value="{{ $user->agent?->nom }} {{ $user->agent?->prenom }}">
                <small class="form-text text-muted">Non modifiable</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control" disabled value="{{ $user->email }}">
                <small class="form-text text-muted">Email professionnel (automatique)</small>
            </div>

            <div class="col-12">
                <label for="role_id" class="form-label fw-semibold">Rôle</label>
                <select class="form-select @error('role_id') is-invalid @enderror"
                        id="role_id" name="role_id">
                    <option value="">– Aucun rôle –</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                            {{ $role->nom_role }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12"><div class="form-section-title"><i class="fas fa-lock me-2"></i>Changer le mot de passe</div></div>

            <div class="col-12">
                <div class="alert alert-info mb-0" style="font-size:.85rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    Laissez vide pour garder le mot de passe actuel.
                </div>
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label fw-semibold">Nouveau mot de passe</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror"
                       id="password" name="password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">Minimum 8 caractères</small>
            </div>

            <div class="col-md-6">
                <label for="password_confirmation" class="form-label fw-semibold">Confirmer</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Enregistrer
                </button>
                <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </form>
</div>

@if($user->id === auth()->id())
<div class="alert alert-info mt-3" style="max-width:680px;font-size:.85rem;">
    <i class="fas fa-user-check me-1"></i> C'est votre propre compte.
    Créé le {{ $user->created_at?->format('d/m/Y') }}, dernière modification le {{ $user->updated_at?->format('d/m/Y') }}.
</div>
@endif
@endsection
