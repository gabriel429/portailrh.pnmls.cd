@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-chart-bar me-2"></i> Rapport Mensuel de Pointages</h2>
            <p class="text-muted mb-0">Statistiques d'assiduité et de présence</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <a href="{{ route('rh.pointages.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-2"></i> Liste
                </a>
                <a href="{{ route('rh.pointages.daily') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-calendar-alt me-2"></i> Par Jour
                </a>
                <a href="{{ route('rh.pointages.monthly-report') }}" class="btn btn-primary">
                    <i class="fas fa-chart-bar me-2"></i> Rapport Mensuel
                </a>
            </div>
            <a href="{{ route('rh.pointages.monthly-export', ['month' => $month]) }}" class="btn btn-success">
                <i class="fas fa-download me-2"></i> Excel
            </a>
        </div>
    </div>

    <!-- Sélecteur de mois -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="month" class="form-label">Mois</label>
                    <input type="month" name="month" id="month" class="form-control" value="{{ $month }}">
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i> Voir Rapport
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <h6 class="card-title mb-2">Total Agents</h6>
                    <h3 class="mb-0">{{ $globalStats['total_agents'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body text-center">
                    <h6 class="card-title mb-2">Total Présents</h6>
                    <h3 class="mb-0">{{ $globalStats['total_present'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body text-center">
                    <h6 class="card-title mb-2">Total Absents</h6>
                    <h3 class="mb-0">{{ $globalStats['total_absent'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body text-center">
                    <h6 class="card-title mb-2">Taux Moyen</h6>
                    <h3 class="mb-0">{{ $globalStats['average_attendance'] }}%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau détaillé par agent -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-bottom">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>
                Détail par Agent - {{ $dateDebut->format('F Y') }}
            </h5>
        </div>
        <div class="card-body">
            @if($agentStats->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Agent</th>
                                <th>Matricule</th>
                                <th class="text-center">Jours Travail</th>
                                <th class="text-center">Enregistrés</th>
                                <th class="text-center">Présents</th>
                                <th class="text-center">Absents</th>
                                <th class="text-center">Heures</th>
                                <th class="text-center">Taux Assiduité</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agentStats as $stat)
                                <tr class="agent-row" style="cursor: pointer;" data-agent-id="{{ $stat['agent']->id }}">
                                    <td>
                                        <strong>{{ $stat['agent']->prenom }} {{ $stat['agent']->nom }}</strong>
                                    </td>
                                    <td>{{ $stat['agent']->matricule_pnmls }}</td>
                                    <td class="text-center">{{ $stat['working_days'] }}</td>
                                    <td class="text-center">{{ $stat['recorded'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $stat['present'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $stat['absent'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <strong>{{ $stat['total_hours'] }}h</strong>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="me-2">
                                                @if($stat['attendance_rate'] >= 90)
                                                    <span class="badge bg-success">{{ $stat['attendance_rate'] }}%</span>
                                                @elseif($stat['attendance_rate'] >= 80)
                                                    <span class="badge bg-warning">{{ $stat['attendance_rate'] }}%</span>
                                                @else
                                                    <span class="badge bg-danger">{{ $stat['attendance_rate'] }}%</span>
                                                @endif
                                            </div>
                                            <div style="width: 80px;">
                                                <div class="progress" style="height: 20px;">
                                                    @php
                                                        $rate = $stat['attendance_rate'];
                                                        $barColor = $rate >= 90 ? 'bg-success' : ($rate >= 80 ? 'bg-warning' : 'bg-danger');
                                                    @endphp
                                                    <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $rate }}%" aria-valuenow="{{ $rate }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td class="text-center">-</td>
                                <td class="text-center">{{ $agentStats->sum('recorded') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success">{{ $globalStats['total_present'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger">{{ $globalStats['total_absent'] }}</span>
                                </td>
                                <td class="text-center">{{ $agentStats->sum('total_hours') }}h</td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $globalStats['average_attendance'] }}%</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-chart-bar fa-5x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucune donnée</h5>
                    <p class="text-muted">Il n'y a aucun pointage pour le mois sélectionné</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Résumé -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Période</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Début :</strong> {{ $dateDebut->format('d F Y') }}
                    </p>
                    <p class="mb-2">
                        <strong>Fin :</strong> {{ $dateFin->format('d F Y') }}
                    </p>
                    <p class="mb-0">
                        <strong>Durée :</strong> {{ $dateDebut->diffInDays($dateFin) + 1 }} jours
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0"><i class="fas fa-calculator me-2"></i> Moyennes</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Heures moyennes par agent :</strong> {{ $globalStats['average_hours'] }}h
                    </p>
                    <p class="mb-2">
                        <strong>Taux d'assiduité moyen :</strong> {{ $globalStats['average_attendance'] }}%
                    </p>
                    <p class="mb-0">
                        <strong>Nombre d'agents :</strong> {{ $globalStats['total_agents'] }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de détails de l'agent -->
<div class="modal fade" id="agentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white border-0">
                <div>
                    <h5 class="modal-title mb-0" id="modalAgentName"></h5>
                    <small id="modalAgentMatricule"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="agentDetailsBody">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <a href="#" id="modalEditBtn" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i> Modifier
                </a>
                <a href="#" id="modalViewFullBtn" class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-2"></i> Vue complète
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
    .progress {
        background-color: #e9ecef;
    }

    .agent-row:hover {
        background-color: #f8f9fa !important;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const agentRows = document.querySelectorAll('.agent-row');

    agentRows.forEach(row => {
        row.addEventListener('click', function() {
            const agentId = this.getAttribute('data-agent-id');
            showAgentDetails(agentId);
        });
    });

    function showAgentDetails(agentId) {
        const modal = new bootstrap.Modal(document.getElementById('agentDetailsModal'));

        // Afficher le spinner
        document.getElementById('agentDetailsBody').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        `;

        modal.show();

        // Charger les détails de l'agent
        fetch(`{{ url('/api/agents') }}/${agentId}`)
            .then(response => response.json())
            .then(data => {
                displayAgentDetails(data.agent);
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('agentDetailsBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erreur lors du chargement des détails
                    </div>
                `;
            });
    }

    function displayAgentDetails(agent) {
        // Mettre à jour le header
        document.getElementById('modalAgentName').textContent = `${agent.prenom} ${agent.nom}`;
        document.getElementById('modalAgentMatricule').innerHTML = `
            <span class="badge bg-light text-dark">${agent.matricule_pnmls}</span>
        `;

        // Mettre à jour les boutons
        document.getElementById('modalEditBtn').href = `/rh/agents/${agent.id}/edit`;
        document.getElementById('modalViewFullBtn').href = `/rh/agents/${agent.id}`;

        // Déterminer le badge statut
        let badgeStatut = '';
        if (agent.statut === 'actif') {
            badgeStatut = '<span class="badge bg-success">Actif</span>';
        } else {
            badgeStatut = `<span class="badge bg-secondary">${agent.statut.charAt(0).toUpperCase() + agent.statut.slice(1)}</span>`;
        }

        // Déterminer le badge rôle
        let badgeRole = agent.role ?
            `<span class="badge bg-info">${agent.role.nom_role}</span>` :
            '<span class="badge bg-secondary">Non assigné</span>';

        // Construire le contenu
        const html = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Informations personnelles</h6>
                    <p class="mb-2">
                        <strong class="text-muted">Prénom:</strong><br>
                        ${agent.prenom}
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted">Nom:</strong><br>
                        ${agent.nom}
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted">Email:</strong><br>
                        <a href="mailto:${agent.email}">${agent.email}</a>
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted">Téléphone:</strong><br>
                        ${agent.telephone || 'N/A'}
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Informations professionnelles</h6>
                    <p class="mb-2">
                        <strong class="text-muted">Poste:</strong><br>
                        ${agent.poste_actuel || 'N/A'}
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted">Rôle:</strong><br>
                        ${badgeRole}
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted">Département:</strong><br>
                        ${agent.departement?.nom_dept || 'N/A'}
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted">Statut:</strong><br>
                        ${badgeStatut}
                    </p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Dates importantes</h6>
                    <p class="mb-2">
                        <strong class="text-muted">Date de naissance:</strong><br>
                        ${agent.date_naissance || 'N/A'}
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted">Lieu de naissance:</strong><br>
                        ${agent.lieu_naissance || 'N/A'}
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Détails supplémentaires</h6>
                    <p class="mb-2">
                        <strong class="text-muted">Date d'embauche:</strong><br>
                        ${agent.date_embauche || 'N/A'}
                    </p>
                    <p class="mb-2">
                        <strong class="text-muted">Adresse:</strong><br>
                        ${agent.adresse || 'N/A'}
                    </p>
                </div>
            </div>
        `;

        document.getElementById('agentDetailsBody').innerHTML = html;
    }
});
</script>

<style>
    .progress {
        background-color: #e9ecef;
    }
</style>
@endsection
