@extends('layouts.app')

@section('title', 'Nouveau Pointage - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
<style>
    .pointage-table th, .pointage-table td { vertical-align: middle; }
    .pointage-table input[type="time"] { min-width: 120px; }
    .pointage-table input[type="text"] { min-width: 150px; }
    .pointage-table .agent-name { font-weight: 600; white-space: nowrap; }
    .pointage-table .agent-poste { font-size: 0.85em; color: #6c757d; }
    #agents-table-container { display: none; }
</style>
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-clipboard-check me-2"></i>Saisie des pointages</h1>
                    <p class="rh-sub">Saisie groupee par departement/service. Selectionnez un departement pour afficher ses agents.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.pointages.index') }}" class="btn-rh alt"><i class="fas fa-arrow-left me-1"></i> Retour liste</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="rh-list-card p-3 p-lg-4">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Erreurs de validation</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Filtres : Departement + Date --}}
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="department_id" class="form-label fw-bold">Departement / Service</label>
                    <select class="form-select" id="department_id">
                        <option value="">-- Selectionner un departement --</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date_pointage" class="form-label fw-bold">Date du pointage</label>
                    <input type="date" class="form-control" id="date_pointage" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" id="btn-load-agents">
                        <i class="fas fa-search me-1"></i> Charger
                    </button>
                </div>
            </div>

            {{-- Loading spinner --}}
            <div id="loading-spinner" class="text-center py-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2 text-muted">Chargement des agents...</p>
            </div>

            {{-- Table des agents --}}
            <div id="agents-table-container">
                <form action="{{ route('rh.pointages.store-bulk') }}" method="POST">
                    @csrf
                    <input type="hidden" name="date_pointage" id="form_date_pointage">

                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong id="dept-name"></strong> &mdash;
                        <span id="agent-count"></span> agent(s).
                        Remplissez les heures pour les agents presents. Les lignes vides seront ignorees.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover pointage-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 25%">Agent</th>
                                    <th style="width: 20%">Heure d'arrivee</th>
                                    <th style="width: 20%">Heure de depart</th>
                                    <th style="width: 30%">Observation</th>
                                </tr>
                            </thead>
                            <tbody id="agents-tbody">
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Enregistrer les pointages
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-fill-all">
                            <i class="fas fa-clock me-1"></i> Remplir tout (08:00 - 16:00)
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="btn-clear-all">
                            <i class="fas fa-eraser me-1"></i> Tout effacer
                        </button>
                    </div>
                </form>
            </div>

            {{-- Empty state --}}
            <div id="empty-state" class="text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Selectionnez un departement</h5>
                <p class="text-muted">Choisissez un departement et une date, puis cliquez sur "Charger" pour afficher les agents.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deptSelect = document.getElementById('department_id');
    const dateInput = document.getElementById('date_pointage');
    const btnLoad = document.getElementById('btn-load-agents');
    const spinner = document.getElementById('loading-spinner');
    const tableContainer = document.getElementById('agents-table-container');
    const emptyState = document.getElementById('empty-state');
    const tbody = document.getElementById('agents-tbody');
    const formDate = document.getElementById('form_date_pointage');
    const deptNameEl = document.getElementById('dept-name');
    const agentCountEl = document.getElementById('agent-count');

    btnLoad.addEventListener('click', loadAgents);

    function loadAgents() {
        const deptId = deptSelect.value;
        const date = dateInput.value;

        if (!deptId) {
            alert('Veuillez selectionner un departement.');
            return;
        }
        if (!date) {
            alert('Veuillez selectionner une date.');
            return;
        }

        // Update hidden form field
        formDate.value = date;

        // Show spinner
        spinner.style.display = 'block';
        tableContainer.style.display = 'none';
        emptyState.style.display = 'none';

        fetch(`{{ route('rh.pointages.agents-by-department') }}?department_id=${deptId}`)
            .then(res => res.json())
            .then(agents => {
                spinner.style.display = 'none';

                if (agents.length === 0) {
                    emptyState.innerHTML = `
                        <i class="fas fa-users-slash fa-3x text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Aucun agent actif dans ce departement</h5>
                    `;
                    emptyState.style.display = 'block';
                    return;
                }

                // Update info
                deptNameEl.textContent = deptSelect.options[deptSelect.selectedIndex].text;
                agentCountEl.textContent = agents.length;

                // Build table rows
                tbody.innerHTML = '';
                agents.forEach((agent, index) => {
                    const fullName = `${agent.prenom} ${agent.nom}`;
                    const poste = agent.poste_actuel || '';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td>
                            <div class="agent-name">${fullName}</div>
                            <div class="agent-poste">${poste}</div>
                            <input type="hidden" name="pointages[${index}][agent_id]" value="${agent.id}">
                        </td>
                        <td>
                            <input type="time" class="form-control heure-entree" name="pointages[${index}][heure_entree]">
                        </td>
                        <td>
                            <input type="time" class="form-control heure-sortie" name="pointages[${index}][heure_sortie]">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="pointages[${index}][observations]" placeholder="Observation...">
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                tableContainer.style.display = 'block';
            })
            .catch(() => {
                spinner.style.display = 'none';
                emptyState.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erreur lors du chargement des agents.
                    </div>
                `;
                emptyState.style.display = 'block';
            });
    }

    // Fill all with default hours
    document.getElementById('btn-fill-all').addEventListener('click', function () {
        document.querySelectorAll('.heure-entree').forEach(input => { input.value = '08:00'; });
        document.querySelectorAll('.heure-sortie').forEach(input => { input.value = '16:00'; });
    });

    // Clear all
    document.getElementById('btn-clear-all').addEventListener('click', function () {
        document.querySelectorAll('.heure-entree').forEach(input => { input.value = ''; });
        document.querySelectorAll('.heure-sortie').forEach(input => { input.value = ''; });
        document.querySelectorAll('input[name*="observations"]').forEach(input => { input.value = ''; });
    });
});
</script>
@endsection
