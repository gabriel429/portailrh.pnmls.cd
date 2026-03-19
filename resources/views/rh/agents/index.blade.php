@extends('layouts.app')

@section('title', 'Agents RH - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-users me-2"></i>Gestion des agents</h1>
                    <p class="rh-sub">Administrez les profils PNMLS, roles, statuts et informations administratives.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools d-flex gap-2">
                        <button type="button" class="btn-rh main" data-bs-toggle="modal" data-bs-target="#exportModal" style="background:#28a745;border-color:#28a745;">
                            <i class="fas fa-file-csv me-1"></i> Exporter
                        </button>
                        <a href="{{ route('rh.agents.create') }}" class="btn-rh main"><i class="fas fa-user-plus me-1"></i> Ajouter un agent</a>
                    </div>
                </div>
            </div>

            {{-- Search bar --}}
            <div class="row">
                <div class="col-lg-12">
                    <form action="{{ route('rh.agents.index') }}" method="GET">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher... (nom, email, matricule, province, grade, fonction, niveau etude)"
                                   value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        @if($totalAgents > 0)
            {{-- Total d'agents --}}
            <div class="alert alert-info mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Total:</strong> {{ $totalAgents }} agent{{ $totalAgents > 1 ? 's' : '' }} enregistré{{ $totalAgents > 1 ? 's' : '' }}
            </div>

            {{-- Agents groupés par organe --}}
            @foreach($agentsByOrgane as $organeKey => $organeData)
                <div class="card mb-4 border-0 shadow-sm" style="border-top: 4px solid {{ $organeData['color'] }};">
                    <div class="card-header" style="background-color: {{ $organeData['bg'] }}; border: none;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-0" style="color: {{ $organeData['color'] }};">
                                    <i class="fas {{ $organeData['icon'] }} me-2"></i>{{ $organeData['label'] }}
                                </h5>
                                <small class="text-muted">{{ count($organeData['agents']) }} agent{{ count($organeData['agents']) > 1 ? 's' : '' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="rh-table-wrap">
                            <table class="rh-table">
                                <thead>
                                    <tr>
                                        <th>Nom et Prenom</th>
                                        <th>Email privé</th>
                                        <th>Email professionnel</th>
                                        <th>Téléphone</th>
                                        <th>Poste</th>
                                        @if($organeKey === 'Secrétariat Exécutif National')
                                            <th>Département/Service</th>
                                        @else
                                            <th>Province</th>
                                        @endif
                                        <th>Matricule de l'État</th>
                                        <th>Ancienneté</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($organeData['agents'] as $agent)
                                        <tr class="agent-row" style="cursor: pointer;" data-agent-id="{{ $agent->id }}">
                                            <td>{{ $agent->prenom }} {{ $agent->nom }}</td>
                                            <td>{{ $agent->email_prive ?? 'N/A' }}</td>
                                            <td>{{ $agent->email_professionnel ?? 'N/A' }}</td>
                                            <td>{{ $agent->telephone ?? 'N/A' }}</td>
                                            <td>{{ $agent->poste_actuel ?? 'N/A' }}</td>
                                            <td>
                                                @if($agent->organe === 'Secrétariat Exécutif National')
                                                    @if($agent->departement)
                                                        {{ $agent->departement->nom }}
                                                    @else
                                                        Service rattaché au SEN
                                                    @endif
                                                @else
                                                    {{ $agent->province?->nom ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <td>{{ $agent->matricule_etat ?? 'N/A' }}</td>
                                            <td>
                                                @if($agent->annee_engagement_programme)
                                                    {{ now()->year - $agent->annee_engagement_programme }} an{{ now()->year - $agent->annee_engagement_programme > 1 ? 's' : '' }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($agent->statut === 'actif')
                                                    <span class="rh-pill st-ok">Actif</span>
                                                @elseif($agent->statut === 'suspendu')
                                                    <span class="rh-pill st-mid">Suspendu</span>
                                                @else
                                                    <span class="rh-pill st-neutral">{{ ucfirst($agent->statut) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="rh-list-card text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Aucun agent</h5>
                <p class="text-muted">Il n'y a aucun agent enregistre.</p>
                <a href="{{ route('rh.agents.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i> Ajouter un agent
                </a>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header" style="background:#28a745;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-file-csv me-2"></i>Exporter la liste des agents</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="{{ route('rh.agents.export') }}" method="GET">
                    <div class="mb-3">
                        <label for="export_organe" class="form-label fw-bold">Organe</label>
                        <select id="export_organe" name="organe" class="form-select">
                            <option value="tous">Tous les organes</option>
                            <option value="SEN">Secrétariat Exécutif National (SEN)</option>
                            <option value="SEP">Secrétariat Exécutif Provincial (SEP)</option>
                            <option value="SEL">Secrétariat Exécutif Local (SEL)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="export_province_group" style="display:none;">
                        <label for="export_province" class="form-label fw-bold">Province</label>
                        <select id="export_province" name="province_id" class="form-select">
                            <option value="">Toutes les provinces</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->nom_province ?? $province->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="export_dept_group" style="display:none;">
                        <label for="export_departement" class="form-label fw-bold">Département</label>
                        <select id="export_departement" name="departement_id" class="form-select">
                            <option value="">Tous les départements</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Le fichier CSV sera téléchargé et peut être ouvert avec Excel.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="exportForm" class="btn btn-success">
                    <i class="fas fa-download me-1"></i> Télécharger CSV
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="agentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header rh-modal-header border-0">
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
                    <i class="fas fa-external-link-alt me-2"></i> Vue complete
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

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

        document.getElementById('agentDetailsBody').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        `;

        modal.show();

        fetch(`{{ url('/api/agents') }}/${agentId}`)
            .then(response => response.json())
            .then(data => {
                displayAgentDetails(data.agent);
            })
            .catch(() => {
                document.getElementById('agentDetailsBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erreur lors du chargement des details
                    </div>
                `;
            });
    }

    function esc(str) {
        if (!str) return 'N/A';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function displayAgentDetails(agent) {
        document.getElementById('modalAgentName').textContent = `${agent.prenom || ''} ${agent.nom || ''}`;
        const matriculeBadge = document.getElementById('modalAgentMatricule');
        matriculeBadge.textContent = '';
        const badge = document.createElement('span');
        badge.className = 'badge bg-light text-dark';
        badge.textContent = agent.id_agent || '';
        matriculeBadge.appendChild(badge);

        document.getElementById('modalEditBtn').href = `/rh/agents/${parseInt(agent.id)}/edit`;
        document.getElementById('modalViewFullBtn').href = `/rh/agents/${parseInt(agent.id)}`;

        let badgeStatut = '';
        if (agent.statut === 'actif') {
            badgeStatut = '<span class="rh-pill st-ok">Actif</span>';
        } else if (agent.statut === 'suspendu') {
            badgeStatut = '<span class="rh-pill st-mid">Suspendu</span>';
        } else {
            badgeStatut = `<span class="rh-pill st-neutral">${esc(agent.statut)}</span>`;
        }

        const roleName = agent.role ? esc(agent.role.nom) : 'Non assigné';
        let badgeRole = agent.role
            ? `<span class="rh-pill st-mid">${roleName}</span>`
            : '<span class="rh-pill st-neutral">Non assigné</span>';

        const html = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Informations personnelles</h6>
                    <p class="mb-2"><strong class="text-muted">Prenom:</strong><br>${esc(agent.prenom)}</p>
                    <p class="mb-2"><strong class="text-muted">Nom:</strong><br>${esc(agent.nom)}</p>
                    <p class="mb-2"><strong class="text-muted">Email:</strong><br>${esc(agent.email)}</p>
                    <p class="mb-2"><strong class="text-muted">Telephone:</strong><br>${esc(agent.telephone)}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Informations professionnelles</h6>
                    <p class="mb-2"><strong class="text-muted">Poste:</strong><br>${esc(agent.poste_actuel)}</p>
                    <p class="mb-2"><strong class="text-muted">Role:</strong><br>${badgeRole}</p>
                    <p class="mb-2"><strong class="text-muted">Departement:</strong><br>${esc(agent.departement?.nom)}</p>
                    <p class="mb-2"><strong class="text-muted">Statut:</strong><br>${badgeStatut}</p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Dates importantes</h6>
                    <p class="mb-2"><strong class="text-muted">Date de naissance:</strong><br>${esc(agent.date_naissance)}</p>
                    <p class="mb-2"><strong class="text-muted">Lieu de naissance:</strong><br>${esc(agent.lieu_naissance)}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Details supplementaires</h6>
                    <p class="mb-2"><strong class="text-muted">Date d'embauche:</strong><br>${esc(agent.date_embauche)}</p>
                    <p class="mb-2"><strong class="text-muted">Adresse:</strong><br>${esc(agent.adresse)}</p>
                </div>
            </div>
        `;

        document.getElementById('agentDetailsBody').innerHTML = html;
    }
    // Export modal: show/hide filters based on organe selection
    const organeSelect = document.getElementById('export_organe');
    const provinceGroup = document.getElementById('export_province_group');
    const deptGroup = document.getElementById('export_dept_group');

    function updateExportFilters() {
        const val = organeSelect.value;
        // Province: show for SEP, SEL, or tous
        provinceGroup.style.display = (val === 'SEP' || val === 'SEL' || val === 'tous') ? 'block' : 'none';
        // Department: show for SEN or tous
        deptGroup.style.display = (val === 'SEN' || val === 'tous') ? 'block' : 'none';

        // Reset hidden selects
        if (provinceGroup.style.display === 'none') document.getElementById('export_province').value = '';
        if (deptGroup.style.display === 'none') document.getElementById('export_departement').value = '';
    }

    organeSelect.addEventListener('change', updateExportFilters);
    updateExportFilters();
});
</script>
@endsection
