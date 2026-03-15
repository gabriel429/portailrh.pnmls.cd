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
                <h2><i class="fas fa-edit me-2"></i> Modifier la Demande</h2>
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

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <!-- Infos demande (readonly) -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Agent</h6>
                            <p class="mb-0">
                                <strong>{{ $request->agent->prenom }} {{ $request->agent->nom }}</strong>
                                <br>
                                <small class="text-muted">{{ $request->agent->id }}</small>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Type</h6>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ ucfirst($request->type) }}</span>
                            </p>
                        </div>
                        <div class="col-md-6 mt-3">
                            <h6 class="text-muted mb-1">Période</h6>
                            <p class="mb-0">
                                Du {{ $request->date_debut->format('d/m/Y') }}
                                @if($request->date_fin)
                                    au {{ $request->date_fin->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mt-3">
                            <h6 class="text-muted mb-1">Description</h6>
                            <p class="mb-0">{{ Str::limit($request->description, 50) }}</p>
                        </div>
                    </div>

                    <hr>

                    <!-- Formulaire de modification -->
                    <form method="POST" action="{{ route('requests.update', $request) }}">
                        @csrf
                        @method('PUT')

                        <!-- Statut -->
                        <div class="mb-4">
                            <label for="statut" class="form-label">
                                <strong>Statut</strong>
                                <span class="text-danger">*</span>
                            </label>
                            <select name="statut" id="statut" class="form-select @error('statut') is-invalid @enderror" required>
                                <option value="en_attente" {{ $request->statut === 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="approuvé" {{ $request->statut === 'approuvé' ? 'selected' : '' }}>Approuvé</option>
                                <option value="rejeté" {{ $request->statut === 'rejeté' ? 'selected' : '' }}>Rejeté</option>
                                <option value="annulé" {{ $request->statut === 'annulé' ? 'selected' : '' }}>Annulé</option>
                            </select>
                            @error('statut')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Remarques -->
                        <div class="mb-4">
                            <label for="remarques" class="form-label">
                                <strong>Remarques</strong> (optionnel)
                            </label>
                            <textarea name="remarques" id="remarques" rows="4" class="form-control @error('remarques') is-invalid @enderror" placeholder="Ajouter un commentaire...">{{ old('remarques', $request->remarques) }}</textarea>
                            <small class="text-muted">Expliquez votre décision ou ajoutez un commentaire</small>
                            @error('remarques')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('requests.show', $request) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
