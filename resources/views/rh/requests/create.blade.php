@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- En-tête -->
            <div class="mb-4">
                <a href="{{ route('requests.index') }}" class="btn btn-link text-muted mb-3">
                    <i class="fas fa-arrow-left me-2"></i> Retour
                </a>
                <h2><i class="fas fa-plus-circle me-2"></i> Nouvelle Demande</h2>
            </div>

            <!-- Messages d'erreur -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreurs :</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('requests.store') }}">
                        @csrf

                        <!-- Agent -->
                        <div class="mb-4">
                            <label for="agent_id" class="form-label">
                                <strong>Agent</strong>
                                <span class="text-danger">*</span>
                            </label>
                            <select name="agent_id" id="agent_id" class="form-select @error('agent_id') is-invalid @enderror" required>
                                <option value="">-- Sélectionner un agent --</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->prenom }} {{ $agent->nom }} (ID: {{ $agent->id }})
                                    </option>
                                @endforeach
                            </select>
                            @error('agent_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Type de demande -->
                        <div class="mb-4">
                            <label for="type" class="form-label">
                                <strong>Type de demande</strong>
                                <span class="text-danger">*</span>
                            </label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">-- Sélectionner un type --</option>
                                <option value="congé" {{ old('type') === 'congé' ? 'selected' : '' }}>Congé</option>
                                <option value="absence" {{ old('type') === 'absence' ? 'selected' : '' }}>Absence</option>
                                <option value="permission" {{ old('type') === 'permission' ? 'selected' : '' }}>Permission</option>
                                <option value="formation" {{ old('type') === 'formation' ? 'selected' : '' }}>Formation</option>
                                <option value="renforcement_capacites" {{ old('type') === 'renforcement_capacites' ? 'selected' : '' }}>Renforcement des capacités</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                <strong>Description</strong>
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Décrivez votre demande..." required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Dates -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="date_debut" class="form-label">
                                    <strong>Date de début</strong>
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="date_debut" id="date_debut" class="form-control @error('date_debut') is-invalid @enderror" value="{{ old('date_debut') }}" required>
                                @error('date_debut')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="date_fin" class="form-label">
                                    <strong>Date de fin</strong> (optionnel)
                                </label>
                                <input type="date" name="date_fin" id="date_fin" class="form-control @error('date_fin') is-invalid @enderror" value="{{ old('date_fin') }}">
                                @error('date_fin')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('requests.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Créer la demande
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
