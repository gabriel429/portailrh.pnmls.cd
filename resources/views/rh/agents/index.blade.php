@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-users me-2"></i> Gestion des Agents</h2>
            <p class="text-muted mb-0">Administrez les agents du PNMLS</p>
        </div>
        <a href="{{ route('rh.agents.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Ajouter un agent
        </a>
    </div>

    <!-- Tableau des agents -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($agents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Matricule</th>
                                <th>Nom & Prénom</th>
                                <th>Email</th>
                                <th>Poste</th>
                                <th>Rôle</th>
                                <th>Département</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agents as $agent)
                                <tr class="agent-row" style="cursor: pointer;" data-agent-id="{{ $agent->id }}">
                                    <td><strong>{{ $agent->matricule_pnmls }}</strong></td>
                                    <td>{{ $agent->prenom }} {{ $agent->nom }}</td>
                                    <td>{{ $agent->email }}</td>
                                    <td>{{ $agent->poste_actuel }}</td>
                                    <td>
                                        @if($agent->role)
                                            <span class="badge bg-info">{{ $agent->role->nom_role }}</span>
                                        @else
                                            <span class="badge bg-secondary">Non assigné</span>
                                        @endif
                                    </td>
                                    <td>{{ $agent->departement?->nom_dept ?? 'N/A' }}</td>
                                    <td>
                                        @if($agent->statut === 'actif')
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($agent->statut) }}</span>
                                        @endif
                                    </td>
                                    <td onclick="event.stopPropagation();">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('rh.agents.show', $agent) }}" class="btn btn-outline-primary" title="Détails complets">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <a href="{{ route('rh.agents.edit', $agent) }}" class="btn btn-outline-warning" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('rh.agents.destroy', $agent) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Affichage {{ $agents->firstItem() ?? 0 }} à {{ $agents->lastItem() ?? 0 }}
                        sur {{ $agents->total() }} agents
                    </div>
                    {{ $agents->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-5x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucun agent</h5>
                    <p class="text-muted">Il n'y a aucun agent enregistré</p>
                    <a href="{{ route('rh.agents.create') }}" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-2"></i> Ajouter un agent
                    </a>
                </div>
            @endif
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
    .agent-row:hover {
        background-color: #f8f9fa !important;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    .badge-status {
        font-size: 0.75rem;
        padding: 0.35rem 0.6rem;
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
