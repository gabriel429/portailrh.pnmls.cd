@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Photo et infos principales -->
    <div class="row mb-4">
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    @if($agent->photo)
                        <img src="{{ asset($agent->photo) }}" alt="{{ $agent->prenom }}" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px;">
                            <i class="fas fa-user fa-5x text-muted"></i>
                        </div>
                    @endif

                    <h4 class="mb-0">{{ $agent->prenom }} {{ $agent->nom }}</h4>
                    <p class="text-muted mb-2">{{ $agent->poste_actuel }}</p>

                    @if($agent->role)
                        <span class="badge bg-info badge-pill">{{ $agent->role->nom_role }}</span>
                    @endif

                    @if(auth()->user()->id === $agent->id)
                        <div class="mt-3">
                            <a href="{{ route('profile.edit', $agent) }}" class="btn btn-sm btn-outline-primary w-100">
                                <i class="fas fa-edit me-2"></i> Modifier
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Infos de contact -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-address-card me-2"></i> Informations</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Email</small>
                        <p class="mb-0">{{ $agent->email }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Téléphone</small>
                        <p class="mb-0">{{ $agent->telephone ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Matricule</small>
                        <p class="mb-0">
                            <strong>{{ $agent->matricule_pnmls }}</strong>
                        </p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Statut</small>
                        <p class="mb-0">
                            @if($agent->statut === 'actif')
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">{{ $agent->statut }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profil détaillé -->
        <div class="col-lg-9">
            <!-- Carte principale -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user me-2"></i> Profil Personnel</h5>

                    <div class="row mt-3">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Lieu de naissance</small>
                            <p>{{ $agent->lieu_naissance ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Date de naissance</small>
                            <p>{{ $agent->date_naissance ? $agent->date_naissance->format('d/m/Y') : 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Situation familiale</small>
                            <p>{{ ucfirst($agent->situation_familiale) ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Nombre d'enfants</small>
                            <p>{{ $agent->nombre_enfants ?? 0 }}</p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <small class="text-muted">Adresse</small>
                            <p>{{ $agent->adresse ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations professionnelles -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-briefcase me-2"></i> Informations Professionnelles</h5>

                    <div class="row mt-3">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Poste actuel</small>
                            <p>{{ $agent->poste_actuel }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Département</small>
                            <p>{{ $agent->department?->nom_dept ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Province</small>
                            <p>{{ $agent->province?->nom ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Date d'embauche</small>
                            <p>{{ $agent->date_embauche ? $agent->date_embauche->format('d/m/Y') : 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents récents -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-alt me-2"></i> Documents ({{ $agent->documents->count() }})</h5>

                    @if($agent->documents->count() > 0)
                        <div class="table-responsive mt-3">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Catégorie</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($agent->documents->take(5) as $doc)
                                        <tr>
                                            <td>
                                                <i class="fas fa-file me-2"></i>
                                                {{ Str::limit($doc->nom_document, 30) }}
                                            </td>
                                            <td><span class="badge bg-light text-dark">{{ $doc->categorie }}</span></td>
                                            <td>{{ $doc->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Aucun document</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($agent->documents->count() > 5)
                            <div class="text-center mt-3">
                                <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-secondary">
                                    Voir tous les documents
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-light text-center py-4 mt-3">
                            <p class="text-muted mb-0">Aucun document pour le moment</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
