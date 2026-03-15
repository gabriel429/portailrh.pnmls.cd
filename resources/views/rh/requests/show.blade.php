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
                <h2><i class="fas fa-file-alt me-2"></i> Demande #{{ $request->id }}</h2>
            </div>

            <!-- Messages -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <!-- Statut -->
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h5 class="mb-1">Statut</h5>
                            @switch($request->statut)
                                @case('en_attente')
                                    <span class="badge bg-warning text-dark" style="font-size: 1em;">En attente</span>
                                    @break
                                @case('approuvé')
                                    <span class="badge bg-success" style="font-size: 1em;">Approuvé</span>
                                    @break
                                @case('rejeté')
                                    <span class="badge bg-danger" style="font-size: 1em;">Rejeté</span>
                                    @break
                                @case('annulé')
                                    <span class="badge bg-secondary" style="font-size: 1em;">Annulé</span>
                                    @break
                            @endswitch
                        </div>
                        <div>
                            <small class="text-muted">Créée le {{ $request->created_at->format('d/m/Y à H:i') }}</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Informations agent -->
                    <div class="mb-4">
                        <h5 class="mb-3">Agent</h5>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <strong>{{ $request->agent->prenom }} {{ $request->agent->nom }}</strong>
                                <br>
                                <small class="text-muted">{{ $request->agent->id_agent }}</small>
                                <br>
                                <small class="text-muted">{{ $request->agent->poste_actuel }}</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Détails de la demande -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-2">Type</h5>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ ucfirst($request->type) }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-2">Période</h5>
                            <p class="mb-0">
                                Du <strong>{{ $request->date_debut->format('d/m/Y') }}</strong>
                                @if($request->date_fin)
                                    au <strong>{{ $request->date_fin->format('d/m/Y') }}</strong>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <h5 class="mb-2">Description</h5>
                        <div class="bg-light p-3 rounded">
                            {{ $request->description }}
                        </div>
                    </div>

                    <!-- Remarques -->
                    @if($request->remarques)
                        <div class="mb-4">
                            <h5 class="mb-2">Remarques</h5>
                            <div class="alert alert-info mb-0">
                                {{ $request->remarques }}
                            </div>
                        </div>
                    @endif

                    <hr>

                    <!-- Boutons d'action -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('requests.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Retour
                        </a>
                        <a href="{{ route('requests.edit', $request) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i> Modifier
                        </a>
                        <form method="POST" action="{{ route('requests.destroy', $request) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette demande ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i> Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
