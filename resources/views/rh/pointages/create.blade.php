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
    .row-recorded { background-color: #f0fdf4; }
    .badge-recorded { font-size: 0.75em; }
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
                    @if($isCAF)
                        <p class="rh-sub">Saisie des presences pour la province <strong>{{ $cafProvince->nom }}</strong>.</p>
                    @else
                        <p class="rh-sub">Saisie groupee par departement/service. Selectionnez un departement pour afficher ses agents.</p>
                    @endif
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

            @if($isCAF)
                {{-- ============================================================ --}}
                {{-- MODE CAF (SEP/SEL) : masque direct avec agents de la province --}}
                {{-- ============================================================ --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="date_pointage_caf" class="form-label fw-bold">Date du pointage</label>
                        <input type="date" class="form-control" id="date_pointage_caf" value="{{ $datePointage }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="#" id="btn-reload-caf" class="btn btn-primary w-100">
                            <i class="fas fa-sync-alt me-1"></i> Actualiser
                        </a>
                    </div>
                </div>

                <form action="{{ route('rh.pointages.store-bulk') }}" method="POST" id="caf-form">
                    @csrf
                    <input type="hidden" name="date_pointage" id="form_date_caf" value="{{ $datePointage }}">

                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <strong>{{ $cafProvince->nom }}</strong> &mdash;
                        {{ $cafAgents->count() }} agent(s).
                        @if($cafPointages->count() > 0)
                            <span class="badge bg-success badge-recorded ms-2">{{ $cafPointages->count() }} deja pointe(s)</span>
                        @endif
                        <br><small class="text-muted">Les heures existantes sont pre-remplies. Vous pouvez completer les heures de sortie et re-enregistrer.</small>
                    </div>

                    @if($cafAgents->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover pointage-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 25%">Agent</th>
                                        <th style="width: 20%">Arrivee (Avant-midi)</th>
                                        <th style="width: 20%">Sortie (Apres-midi)</th>
                                        <th style="width: 30%">Observation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cafAgents as $index => $agent)
                                        @php $p = $cafPointages->get($agent->id); @endphp
                                        <tr class="{{ $p ? 'row-recorded' : '' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="agent-name">
                                                    {{ $agent->prenom }} {{ $agent->nom }}
                                                    @if($p)
                                                        <span class="badge bg-success badge-recorded ms-1" title="Pointage deja enregistre">
                                                            <i class="fas fa-check"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="agent-poste">{{ $agent->poste_actuel ?? '' }}</div>
                                                <input type="hidden" name="pointages[{{ $index }}][agent_id]" value="{{ $agent->id }}">
                                            </td>
                                            <td>
                                                <input type="time" class="form-control heure-entree"
                                                       name="pointages[{{ $index }}][heure_entree]"
                                                       value="{{ $p && $p->heure_entree ? \Carbon\Carbon::parse($p->heure_entree)->format('H:i') : '' }}">
                                            </td>
                                            <td>
                                                <input type="time" class="form-control heure-sortie"
                                                       name="pointages[{{ $index }}][heure_sortie]"
                                                       value="{{ $p && $p->heure_sortie ? \Carbon\Carbon::parse($p->heure_sortie)->format('H:i') : '' }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control"
                                                       name="pointages[{{ $index }}][observations]"
                                                       placeholder="Observation..."
                                                       value="{{ $p->observations ?? '' }}">
                                            </td>
                                        </tr>
                                    @endforeach
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
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users-slash fa-3x text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">Aucun agent actif dans la province {{ $cafProvince->nom }}</h5>
                        </div>
                    @endif
                </form>

            @else
                {{-- ============================================================ --}}
                {{-- MODE SEN : selection par departement                         --}}
                {{-- ============================================================ --}}

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

                <div id="loading-spinner" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement des agents...</p>
                </div>

                <div id="agents-table-container">
                    <form action="{{ route('rh.pointages.store-bulk') }}" method="POST">
                        @csrf
                        <input type="hidden" name="date_pointage" id="form_date_pointage">

                        <div class="alert alert-info py-2 mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong id="dept-name"></strong> &mdash;
                            <span id="agent-count"></span> agent(s).
                            <span id="recorded-count"></span>
                            <br><small class="text-muted">Les heures existantes sont pre-remplies. Vous pouvez completer les heures de sortie et re-enregistrer.</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover pointage-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 25%">Agent</th>
                                        <th style="width: 20%">Arrivee (Avant-midi)</th>
                                        <th style="width: 20%">Sortie (Apres-midi)</th>
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
                            <button type="button" class="btn btn-outline-secondary btn-fill-all-js">
                                <i class="fas fa-clock me-1"></i> Remplir tout (08:00 - 16:00)
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-clear-all-js">
                                <i class="fas fa-eraser me-1"></i> Tout effacer
                            </button>
                        </div>
                    </form>
                </div>

                <div id="empty-state" class="text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Selectionnez un departement</h5>
                    <p class="text-muted">Choisissez un departement et une date, puis cliquez sur "Charger" pour afficher les agents.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Boutons Remplir / Effacer (communs aux deux modes) ──
    document.querySelectorAll('#btn-fill-all, .btn-fill-all-js').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.heure-entree').forEach(input => { if (!input.value) input.value = '08:00'; });
            document.querySelectorAll('.heure-sortie').forEach(input => { if (!input.value) input.value = '16:00'; });
        });
    });

    document.querySelectorAll('#btn-clear-all, .btn-clear-all-js').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.heure-entree').forEach(input => { input.value = ''; });
            document.querySelectorAll('.heure-sortie').forEach(input => { input.value = ''; });
            document.querySelectorAll('input[name*="observations"]').forEach(input => { input.value = ''; });
        });
    });

    @if($isCAF)
        // ── MODE CAF : recharger la page avec la date selectionnee ──
        const dateCaf = document.getElementById('date_pointage_caf');
        const formDateCaf = document.getElementById('form_date_caf');

        dateCaf.addEventListener('change', function () {
            formDateCaf.value = this.value;
        });

        document.getElementById('btn-reload-caf').addEventListener('click', function (e) {
            e.preventDefault();
            const date = dateCaf.value;
            window.location.href = `{{ route('rh.pointages.create') }}?date=${date}`;
        });
    @else
        // ── MODE SEN : chargement AJAX par departement ──
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
        const recordedCountEl = document.getElementById('recorded-count');

        btnLoad.addEventListener('click', loadAgents);

        function loadAgents() {
            const deptId = deptSelect.value;
            const date = dateInput.value;

            if (!deptId) { alert('Veuillez selectionner un departement.'); return; }
            if (!date) { alert('Veuillez selectionner une date.'); return; }

            formDate.value = date;
            spinner.style.display = 'block';
            tableContainer.style.display = 'none';
            emptyState.style.display = 'none';

            fetch(`{{ route('rh.pointages.agents-by-department') }}?department_id=${deptId}&date=${date}`)
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

                    deptNameEl.textContent = deptSelect.options[deptSelect.selectedIndex].text;
                    agentCountEl.textContent = agents.length;

                    // Count already recorded
                    const recorded = agents.filter(a => a.pointage_existant).length;
                    recordedCountEl.innerHTML = recorded > 0
                        ? `<span class="badge bg-success badge-recorded ms-2">${recorded} deja pointe(s)</span>`
                        : '';

                    tbody.innerHTML = '';
                    agents.forEach((agent, index) => {
                        const p = agent.pointage_existant;
                        const rowClass = p ? 'row-recorded' : '';
                        const checkBadge = p ? '<span class="badge bg-success badge-recorded ms-1"><i class="fas fa-check"></i></span>' : '';

                        const tr = document.createElement('tr');
                        tr.className = rowClass;
                        tr.innerHTML = `
                            <td>${index + 1}</td>
                            <td>
                                <div class="agent-name">${agent.prenom} ${agent.nom} ${checkBadge}</div>
                                <div class="agent-poste">${agent.poste_actuel || ''}</div>
                                <input type="hidden" name="pointages[${index}][agent_id]" value="${agent.id}">
                            </td>
                            <td><input type="time" class="form-control heure-entree" name="pointages[${index}][heure_entree]" value="${p?.heure_entree || ''}"></td>
                            <td><input type="time" class="form-control heure-sortie" name="pointages[${index}][heure_sortie]" value="${p?.heure_sortie || ''}"></td>
                            <td><input type="text" class="form-control" name="pointages[${index}][observations]" placeholder="Observation..." value="${p?.observations || ''}"></td>
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
    @endif
});
</script>
@endsection
