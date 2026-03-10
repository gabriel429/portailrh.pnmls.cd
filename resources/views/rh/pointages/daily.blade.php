@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-calendar-alt me-2"></i> Pointages par Jour</h2>
            <p class="text-muted mb-0">Affichage des présences et absences par jour</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <a href="{{ route('rh.pointages.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-2"></i> Liste
                </a>
                <a href="{{ route('rh.pointages.daily') }}" class="btn btn-primary">
                    <i class="fas fa-calendar-alt me-2"></i> Par Jour
                </a>
                <a href="{{ route('rh.pointages.monthly-report') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-chart-bar me-2"></i> Rapport Mensuel
                </a>
            </div>
            <a href="{{ route('rh.pointages.daily-export', ['date_debut' => $dateDebut, 'date_fin' => $dateFin, 'agent_id' => $agent_id]) }}" class="btn btn-success">
                <i class="fas fa-download me-2"></i> Excel
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date de début</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $dateDebut }}">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date de fin</label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $dateFin }}">
                </div>
                <div class="col-md-4">
                    <label for="agent_id" class="form-label">Agent</label>
                    <select name="agent_id" id="agent_id" class="form-select">
                        <option value="">-- Tous les agents --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ $agent_id == $agent->id ? 'selected' : '' }}>
                                {{ $agent->prenom }} {{ $agent->nom }} ({{ $agent->matricule_pnmls }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pointages par jour -->
    @if($pointagesByDate->count() > 0)
        @foreach($pointagesByDate as $date => $dayPointages)
            @php
                $stats = $dailyStats[$date];
                $dateObj = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
            @endphp
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-check me-2"></i>
                                {{ $dateObj->format('l d F Y') }}
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-info me-2">{{ $stats['count'] }} agents</span>
                            <span class="badge bg-success me-2">{{ $stats['present'] }} présents</span>
                            <span class="badge bg-danger">{{ $stats['absent'] }} absents</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Agent</th>
                                    <th>Matricule</th>
                                    <th>Entrée</th>
                                    <th>Sortie</th>
                                    <th>Heures</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dayPointages as $pointage)
                                    <tr>
                                        <td>
                                            <strong>{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</strong>
                                        </td>
                                        <td>{{ $pointage->agent->matricule_pnmls }}</td>
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
                                        <td>
                                            @if($pointage->heures_travaillees)
                                                <strong>{{ $pointage->heures_travaillees }}h</strong>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pointage->heure_entree)
                                                <span class="badge bg-success-light text-success">Présent</span>
                                            @else
                                                <span class="badge bg-danger-light text-danger">Absent</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($stats['total_hours'] > 0)
                        <div class="text-end text-muted small mt-2">
                            <strong>Total heures du jour :</strong> {{ $stats['total_hours'] }}h
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-5x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucun pointage</h5>
                    <p class="text-muted">Il n'y a aucun pointage enregistré pour la période sélectionnée</p>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    .bg-success-light {
        background-color: #d4edda;
    }
    .bg-danger-light {
        background-color: #f8d7da;
    }
    .text-success {
        color: #155724 !important;
    }
    .text-danger {
        color: #721c24 !important;
    }
</style>
@endsection
