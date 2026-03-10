@extends('layouts.app')

@section('title', 'Dashboard - Portail RH PNMLS')

@section('content')
<div class="container-fluid py-5" style="background-color: #f5f5f5; min-height: calc(100vh - 200px);">
    <div class="container">
        <!-- En-tête -->
        <div class="row mb-5">
            <div class="col-md-8">
                <h1 class="h3 mb-2">Bienvenue, {{ auth()->user()->prenom }} !</h1>
                <p class="text-muted">Tableau de bord personnel - {{ auth()->user()->poste_actuel ?? 'Agent' }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('requests.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i> Nouvelle Demande
                </a>
            </div>
        </div>

        <!-- Cartes résumé -->
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Documents</p>
                                <h4 class="mb-0">{{ auth()->user()->documents->count() }}</h4>
                            </div>
                            <i class="fas fa-folder fa-3x text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Demandes en attente</p>
                                <h4 class="mb-0">{{ auth()->user()->requests()->where('statut', 'en_attente')->count() }}</h4>
                            </div>
                            <i class="fas fa-hourglass-end fa-3x text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Demandes approuvées</p>
                                <h4 class="mb-0">{{ auth()->user()->requests()->where('statut', 'approuvé')->count() }}</h4>
                            </div>
                            <i class="fas fa-check-circle fa-3x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Absences</p>
                                <h4 class="mb-0">{{ auth()->user()->pointages()->where('heure_arrivee', null)->count() }}</h4>
                            </div>
                            <i class="fas fa-calendar-times fa-3x text-danger opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="row">
            <!-- Colonne gauche: Actions rapides -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i> Actions rapides</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('requests.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-plus-circle me-2 text-info"></i> Demander un congé</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="{{ route('requests.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-rocket me-2 text-success"></i> Demander une formation</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="{{ route('documents.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-upload me-2 text-primary"></i> Télève un document</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="{{ route('signalements.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-exclamation-triangle me-2 text-danger"></i> Signaler un abus</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="{{ route('profile.show', auth()->user()) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-user-edit me-2 text-purple"></i> Modifier mon profil</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Colonne droite: Activités récentes -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-clock me-2 text-primary"></i> Activités récentes</h5>
                    </div>
                    <div class="card-body">
                        @if(auth()->user()->requests()->count() > 0)
                            <div class="timeline">
                                @foreach(auth()->user()->requests()->latest()->limit(5)->get() as $request)
                                    <div class="d-flex mb-4">
                                        <div class="me-3">
                                            <div class="rounded-circle p-2" style="background-color: #f0f7ff; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-file-alt text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 fw-bold">Demande de {{ $request->type_demande }}</p>
                                            <p class="text-muted small mb-1">
                                                Statut: <span class="badge bg-{{ $request->statut === 'approuvé' ? 'success' : ($request->statut === 'rejeté' ? 'danger' : 'warning') }}">{{ $request->statut }}</span>
                                            </p>
                                            <p class="text-muted small">{{ $request->created_at->format('d M Y à H:i') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('requests.index') }}" class="btn btn-sm btn-outline-primary w-100">Voir toutes les demandes</a>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucune demande pour le moment</p>
                                <a href="{{ route('requests.create') }}" class="btn btn-sm btn-primary">Faire une demande</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations du compte -->
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informations</h5>
                    </div>
                    <div class="card-body">
                        <dt class="text-muted small">Matricule PNMLS</dt>
                        <dd class="mb-3 fw-bold">{{ auth()->user()->matricule_pnmls }}</dd>

                        <dt class="text-muted small">Département</dt>
                        <dd class="mb-3 fw-bold">{{ auth()->user()->department->nom ?? 'Non assigné' }}</dd>

                        <dt class="text-muted small">Province</dt>
                        <dd class="mb-3 fw-bold">{{ auth()->user()->province->nom ?? 'Non assigné' }}</dd>

                        <dt class="text-muted small">Date d'embauche</dt>
                        <dd class="mb-3 fw-bold">{{ auth()->user()->date_embauche->format('d M Y') }}</dd>

                        <a href="{{ route('profile.show', auth()->user()) }}" class="btn btn-sm btn-outline-primary w-100">Voir mon profil complet</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
