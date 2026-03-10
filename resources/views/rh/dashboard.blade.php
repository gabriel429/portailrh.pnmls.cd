@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="mb-4">
        <h2><i class="fas fa-chart-line me-2"></i> Tableau de Bord RH</h2>
        <p class="text-muted mb-0">Vue d'ensemble des ressources humaines</p>
    </div>

    <!-- Statistiques Agents -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Agents Totaux</p>
                            <h3 class="mb-0 text-primary">{{ $totalAgents }}</h3>
                        </div>
                        <i class="fas fa-users fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Agents Actifs</p>
                            <h3 class="mb-0 text-success">{{ $activeAgents }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Suspendus</p>
                            <h3 class="mb-0 text-warning">{{ $suspendedAgents }}</h3>
                        </div>
                        <i class="fas fa-pause-circle fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Anciens</p>
                            <h3 class="mb-0 text-secondary">{{ $formerAgents }}</h3>
                        </div>
                        <i class="fas fa-user-times fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques Demandes -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Demandes Totales</p>
                            <h3 class="mb-0 text-primary">{{ $totalRequests }}</h3>
                        </div>
                        <i class="fas fa-file-alt fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">En Attente</p>
                            <h3 class="mb-0 text-warning">{{ $pendingRequests }}</h3>
                        </div>
                        <i class="fas fa-hourglass-half fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Approuvées</p>
                            <h3 class="mb-0 text-success">{{ $approvedRequests }}</h3>
                        </div>
                        <i class="fas fa-check fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Rejetées</p>
                            <h3 class="mb-0 text-danger">{{ $rejectedRequests }}</h3>
                        </div>
                        <i class="fas fa-times fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pointages totaux -->
    <div class="row mb-4">
        <div class="col-md-12 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Pointages Enregistrés</p>
                            <h3 class="mb-0 text-primary">{{ $totalAttendance }}</h3>
                        </div>
                        <i class="fas fa-clock fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activités récentes -->
    <div class="row">
        <!-- Demandes récentes -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Demandes Récentes</h5>
                </div>
                <div class="card-body p-0">
                    @if($recentRequests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Agent</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentRequests as $request)
                                        <tr>
                                            <td>
                                                <strong>{{ $request->agent->prenom }} {{ $request->agent->nom }}</strong>
                                            </td>
                                            <td>{{ $request->type ?? 'N/A' }}</td>
                                            <td>
                                                @if($request->statut === 'en attente')
                                                    <span class="badge bg-warning">Attente</span>
                                                @elseif($request->statut === 'approuvé')
                                                    <span class="badge bg-success">Approuvé</span>
                                                @elseif($request->statut === 'rejeté')
                                                    <span class="badge bg-danger">Rejeté</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($request->statut) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $request->created_at?->format('d/m/Y') }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-2 d-block opacity-50"></i>
                            <p class="mb-0">Aucune demande récente</p>
                        </div>
                    @endif
                </div>
                @if($recentRequests->count() > 0)
                    <div class="card-footer bg-light">
                        <a href="{{ route('requests.index') }}" class="text-decoration-none">
                            Voir toutes les demandes <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pointages récents -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-clock me-2 text-primary"></i>Pointages Récents</h5>
                </div>
                <div class="card-body p-0">
                    @if($recentAttendance->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Agent</th>
                                        <th>Date</th>
                                        <th>Entrée</th>
                                        <th>Sortie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentAttendance as $pointage)
                                        <tr>
                                            <td>
                                                <strong>{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</strong>
                                            </td>
                                            <td>
                                                <small>{{ $pointage->date_pointage?->format('d/m/Y') }}</small>
                                            </td>
                                            <td>
                                                @if($pointage->heure_entree)
                                                    <span class="badge bg-success">{{ $pointage->heure_entree }}</span>
                                                @else
                                                    <span class="badge bg-secondary">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($pointage->heure_sortie)
                                                    <span class="badge bg-info">{{ $pointage->heure_sortie }}</span>
                                                @else
                                                    <span class="badge bg-secondary">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-2 d-block opacity-50"></i>
                            <p class="mb-0">Aucun pointage récent</p>
                        </div>
                    @endif
                </div>
                @if($recentAttendance->count() > 0)
                    <div class="card-footer bg-light">
                        <a href="{{ route('rh.pointages.index') }}" class="text-decoration-none">
                            Voir tous les pointages <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2 text-primary"></i>Actions Rapides</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('rh.agents.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-plus me-1"></i> Ajouter Agent
                        </a>
                        <a href="{{ route('rh.pointages.create') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-clock me-1"></i> Enregistrer Pointage
                        </a>
                        <a href="{{ route('rh.agents.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-list me-1"></i> Gestion Agents
                        </a>
                        <a href="{{ route('requests.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-file-alt me-1"></i> Demandes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
