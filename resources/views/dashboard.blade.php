@extends('layouts.app')

@section('title', 'Dashboard - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
@php
    $currentUser = auth()->user();
    $agent = $currentUser->agent;

    // Get pending and approved requests for the agent
    $pendingCount = 0;
    $approvedCount = 0;
    $absenceCount = 0;
    $latestRequests = collect();
    try {
        if ($agent && \Illuminate\Support\Facades\Schema::hasTable('requests')) {
            $pendingCount = $agent->requests()->where('statut', 'en_attente')->count();
            $approvedCount = $agent->requests()->where('statut', 'approuvé')->count();
            $latestRequests = $agent->requests()->latest()->limit(5)->get();
        }
        if ($agent && \Illuminate\Support\Facades\Schema::hasTable('pointages')) {
            $absenceCount = $agent->pointages()->whereNull('heure_entree')->count();
        }
    } catch (\Exception $e) {}

    // Messages du DRH
    $unreadMessages = 0;
    $latestMessages = collect();
    try {
        if ($agent && \Illuminate\Support\Facades\Schema::hasTable('messages')) {
            $unreadMessages = $agent->messages()->where('lu', false)->count();
            $latestMessages = $agent->messages()->with('sender')->latest()->limit(5)->get();
        }
    } catch (\Exception $e) {}

    // Communiqués officiels
    $allCommuniques = collect();
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('communiques')) {
            $allCommuniques = \App\Models\Communique::visibles()->latest()->limit(5)->get();
        }
    } catch (\Exception $e) {}

    // Affectations de l'agent
    $affectations = collect();
    try {
        if ($agent && \Illuminate\Support\Facades\Schema::hasTable('affectations')) {
            $affectations = $agent->affectations()
                ->with('fonction', 'department', 'section', 'province')
                ->orderByDesc('date_debut')
                ->limit(5)
                ->get();
        }
    } catch (\Exception $e) {}

    // Taches assignees a l'agent
    $tachesCount = 0;
    $mesTaches = collect();
    $isDirecteur = false;
    $tachesDirecteur = collect();
    try {
        if ($agent && \Illuminate\Support\Facades\Schema::hasTable('taches')) {
            $tachesCount = \App\Models\Tache::where('agent_id', $agent->id)
                ->whereIn('statut', ['nouvelle', 'en_cours'])->count();
            $mesTaches = \App\Models\Tache::with('createur')
                ->where('agent_id', $agent->id)
                ->whereIn('statut', ['nouvelle', 'en_cours'])
                ->latest()->limit(5)->get();

            $isDirecteur = $currentUser->hasRole('Directeur');
            if ($isDirecteur) {
                $tachesDirecteur = \App\Models\Tache::with('agent')
                    ->where('createur_id', $agent->id)
                    ->latest()->limit(5)->get();
            }
        }
    } catch (\Exception $e) {
        // Table taches pas encore deployee
    }

    // Plan de Travail Annuel
    $planActivitesCount = 0;
    $planActivitesEnCours = collect();
    try {
        if ($agent && \Illuminate\Support\Facades\Schema::hasTable('activite_plans')) {
            $organe = $agent->organe ?? '';
            $planQuery = \App\Models\ActivitePlan::where('annee', now()->year);

            if (str_contains($organe, 'National')) {
                $planQuery->where('niveau_administratif', 'SEN');
                if ($agent->departement_id) {
                    $planQuery->where(function($q) use ($agent) {
                        $q->where('departement_id', $agent->departement_id)
                          ->orWhereNull('departement_id');
                    });
                }
            } elseif (str_contains($organe, 'Provincial')) {
                $planQuery->where('niveau_administratif', 'SEP')
                          ->where('province_id', $agent->province_id);
            } elseif (str_contains($organe, 'Local')) {
                $planQuery->where('niveau_administratif', 'SEL')
                          ->where('province_id', $agent->province_id);
            }

            $planActivitesCount = (clone $planQuery)->whereIn('statut', ['planifiee', 'en_cours'])->count();
            $planActivitesEnCours = (clone $planQuery)->with('createur')
                ->latest()->limit(5)->get();
        }
    } catch (\Exception $e) {}

    // Renforcement des Capacites - Detection du role
    $isRenforcementCapacites = false;
    $renforcementRequests = collect();
    $renforcementDocuments = collect();
    $renfFormationCount = 0;
    $renfFormationPendingCount = 0;
    $renfDocumentsCount = 0;
    try {
        if ($agent) {
            // Detecter via l'affectation active
            if (\Illuminate\Support\Facades\Schema::hasTable('affectations') && \Illuminate\Support\Facades\Schema::hasTable('fonctions')) {
                $renfAffectation = \App\Models\Affectation::where('agent_id', $agent->id)
                    ->where('actif', true)
                    ->with('fonction', 'section')
                    ->first();

                if ($renfAffectation) {
                    $renfFonctionNom = mb_strtolower($renfAffectation->fonction?->nom ?? '');
                    $renfSectionNom = mb_strtolower($renfAffectation->section?->nom ?? '');

                    if (str_contains($renfFonctionNom, 'renforcement') || str_contains($renfSectionNom, 'renforcement')) {
                        $isRenforcementCapacites = true;
                    }
                }
            }

            // Fallback : champ texte fonction de l'agent
            if (!$isRenforcementCapacites) {
                $agentFonction = mb_strtolower($agent->fonction ?? '');
                if (str_contains($agentFonction, 'renforcement')) {
                    $isRenforcementCapacites = true;
                }
            }

            // Charger les donnees si autorise
            if ($isRenforcementCapacites) {
                if (\Illuminate\Support\Facades\Schema::hasTable('requests')) {
                    $renfReqQuery = \App\Models\Request::whereIn('type', ['formation', 'renforcement_capacites']);
                    $renfFormationCount = (clone $renfReqQuery)->count();
                    $renfFormationPendingCount = (clone $renfReqQuery)->where('statut', 'en_attente')->count();
                    $renforcementRequests = $renfReqQuery->with('agent')->latest()->limit(20)->get();
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('documents')) {
                    $renfDocQuery = \App\Models\Document::where('type', 'parcours');
                    $renfDocumentsCount = (clone $renfDocQuery)->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)->count();
                    $renforcementDocuments = $renfDocQuery->with('agent')->latest()->limit(20)->get();
                }
            }
        }
    } catch (\Exception $e) {}
@endphp

<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title">Bonjour, {{ $agent?->prenom }} {{ $agent?->nom }}</h1>
                    <p class="rh-sub">Poste: {{ $agent?->poste_actuel ?? 'Agent' }} | Vue personnelle des demandes, documents et pointages.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('requests.create') }}" class="btn-rh main"><i class="fas fa-plus-circle me-1"></i> Nouvelle demande</a>
                        <a href="{{ route('documents.create') }}" class="btn-rh alt"><i class="fas fa-upload me-1"></i> Ajouter document</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="kpi-grid">
            <a href="{{ route('documents.index') }}" class="kpi text-decoration-none" style="cursor: pointer;">
                <p class="label">Documents</p>
                <h2 class="value">{{ $agent?->documents->count() ?? 0 }}</h2>
                <span class="trend trend-info"><i class="fas fa-folder-open"></i> Dossier personnel</span>
            </a>
            <a href="{{ route('requests.index') }}" class="kpi text-decoration-none" style="cursor: pointer;">
                <p class="label">Demandes en attente</p>
                <h2 class="value">{{ $pendingCount }}</h2>
                <span class="trend trend-mid"><i class="fas fa-hourglass-half"></i> Suivi en cours</span>
            </a>
            <a href="{{ route('requests.index') }}" class="kpi text-decoration-none" style="cursor: pointer;">
                <p class="label">Demandes approuvees</p>
                <h2 class="value">{{ $approvedCount }}</h2>
                <span class="trend trend-ok"><i class="fas fa-check-circle"></i> Historique valide</span>
            </a>
            <a href="{{ route('mes-absences') }}" class="kpi text-decoration-none" style="cursor: pointer;">
                <p class="label">Absences detectees</p>
                <h2 class="value">{{ $absenceCount }}</h2>
                <span class="trend trend-bad"><i class="fas fa-calendar-times"></i> Controle presence</span>
            </a>
            <a href="#messages-drh" class="kpi text-decoration-none" style="cursor: pointer;">
                <p class="label">Messages non lus</p>
                <h2 class="value">{{ $unreadMessages }}</h2>
                <span class="trend {{ $unreadMessages > 0 ? 'trend-mid' : 'trend-ok' }}"><i class="fas fa-envelope"></i> Messagerie DRH</span>
            </a>
            <a href="#communiques-sen" class="kpi text-decoration-none" style="cursor: pointer;">
                <p class="label">Communiqués</p>
                <h2 class="value">{{ $allCommuniques->count() }}</h2>
                <span class="trend {{ $allCommuniques->where('urgence', 'urgent')->count() > 0 ? 'trend-bad' : 'trend-info' }}"><i class="fas fa-bullhorn"></i> Annonces SEN</span>
            </a>
            <a href="{{ route('taches.index') }}" class="kpi text-decoration-none" style="cursor: pointer;">
                <p class="label">Taches actives</p>
                <h2 class="value">{{ $tachesCount }}</h2>
                <span class="trend {{ $tachesCount > 0 ? 'trend-mid' : 'trend-ok' }}"><i class="fas fa-tasks"></i> A traiter</span>
            </a>
            <a href="{{ route('plan-travail.index') }}" class="kpi text-decoration-none" style="cursor: pointer;">
                <p class="label">Plan de Travail</p>
                <h2 class="value">{{ $planActivitesCount }}</h2>
                <span class="trend {{ $planActivitesCount > 0 ? 'trend-mid' : 'trend-ok' }}"><i class="fas fa-calendar-alt"></i> PTA {{ now()->year }}</span>
            </a>
        </section>

        <section class="dash-grid">
            {{-- Colonne gauche : Activités récentes + Parcours + Communiqués + Messages DRH --}}
            <div class="d-flex flex-column gap-3">
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-wave-square me-2 text-info"></i>Activites recentes</h3>
                            <p class="panel-sub">Dernieres evolutions de vos demandes RH.</p>
                        </div>
                        <a href="{{ route('requests.index') }}" class="btn btn-sm btn-outline-primary">Tout voir</a>
                    </header>

                    @if($latestRequests->count() > 0)
                        <div class="activity-list">
                            @foreach($latestRequests as $request)
                                @php
                                    $statusValue = strtolower((string) $request->statut);
                                    $statusClass = str_contains($statusValue, 'approuv')
                                        ? 'st-ok'
                                        : (str_contains($statusValue, 'rejet') ? 'st-bad' : 'st-mid');
                                @endphp
                                <div class="activity-item">
                                    <div class="activity-icon"><i class="fas fa-file-signature"></i></div>
                                    <div>
                                        <p class="activity-title">Demande: {{ $request->type_demande ?? $request->type ?? 'N/A' }}</p>
                                        <span class="status-chip {{ $statusClass }}">{{ ucfirst($request->statut) }}</span>
                                        <p class="activity-time">{{ $request->created_at->format('d/m/Y a H:i') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p class="mb-0">Aucune activite recente.</p>
                        </div>
                    @endif
                </div>

                {{-- Mes taches --}}
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-tasks me-2 text-warning"></i>Mes taches</h3>
                            <p class="panel-sub">Taches qui me sont assignees.</p>
                        </div>
                        <a href="{{ route('taches.index') }}" class="btn btn-sm btn-outline-primary">Tout voir</a>
                    </header>
                    <div class="p-3">
                        @forelse($mesTaches as $tache)
                            <a href="{{ route('taches.show', $tache) }}" class="text-decoration-none d-block">
                            <div class="border-start border-3 {{ $tache->priorite === 'urgente' ? 'border-danger' : ($tache->priorite === 'haute' ? 'border-warning' : 'border-info') }} rounded p-3 mb-2" style="cursor: pointer; transition: box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-dark">
                                            @if($tache->priorite === 'urgente')
                                                <span class="badge bg-danger me-1" style="font-size: 0.65rem;">Urgent</span>
                                            @elseif($tache->priorite === 'haute')
                                                <span class="badge bg-warning text-dark me-1" style="font-size: 0.65rem;">Haute</span>
                                            @endif
                                            {{ $tache->titre }}
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>{{ $tache->createur?->nom_complet }}
                                            @if($tache->date_echeance)
                                                &bull; <i class="fas fa-calendar me-1"></i>{{ $tache->date_echeance->format('d/m/Y') }}
                                            @endif
                                        </small>
                                    </div>
                                    <span class="badge {{ $tache->statut === 'nouvelle' ? 'bg-secondary' : 'bg-primary' }}">
                                        {{ $tache->statut === 'nouvelle' ? 'Nouvelle' : 'En cours' }}
                                    </span>
                                </div>
                            </div>
                            </a>
                        @empty
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-tasks fa-2x mb-2 d-block"></i>
                                <p class="mb-0">Aucune tache en cours.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Taches assignees par le directeur --}}
                @if($isDirecteur && $tachesDirecteur->isNotEmpty())
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-clipboard-list me-2 text-success"></i>Taches assignees</h3>
                            <p class="panel-sub">Taches que vous avez assignees a votre equipe.</p>
                        </div>
                        <a href="{{ route('taches.index') }}" class="btn btn-sm btn-outline-primary">Tout voir</a>
                    </header>
                    <div class="p-3">
                        @foreach($tachesDirecteur as $tache)
                            <a href="{{ route('taches.show', $tache) }}" class="text-decoration-none d-block">
                            @php
                                $tBorderClass = match($tache->statut) {
                                    'terminee' => 'border-success',
                                    'en_cours' => 'border-primary',
                                    default => 'border-secondary',
                                };
                                $tBadgeClass = match($tache->statut) {
                                    'terminee' => 'bg-success',
                                    'en_cours' => 'bg-primary',
                                    default => 'bg-secondary',
                                };
                                $tStatusLabel = match($tache->statut) {
                                    'terminee' => 'Terminee',
                                    'en_cours' => 'En cours',
                                    default => 'Nouvelle',
                                };
                            @endphp
                            <div class="border-start border-3 {{ $tBorderClass }} rounded p-3 mb-2" style="cursor: pointer; transition: box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-dark">{{ $tache->titre }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>{{ $tache->agent?->nom_complet }}
                                        </small>
                                    </div>
                                    <span class="badge {{ $tBadgeClass }}">{{ $tStatusLabel }}</span>
                                </div>
                            </div>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Renforcement des Capacites (conditionnel) --}}
                @if($isRenforcementCapacites)
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-graduation-cap me-2" style="color: #e91e63;"></i>Renforcement des Capacites</h3>
                            <p class="panel-sub">Demandes de formation et documents parcours des agents.</p>
                        </div>
                    </header>

                    {{-- Mini KPIs --}}
                    <div class="row g-2 p-3 pb-0">
                        <div class="col-4">
                            <div class="p-2 rounded text-center" style="background: #fce4ec;">
                                <h5 class="mb-0 fw-bold" style="color: #e91e63;">{{ $renfFormationCount }}</h5>
                                <small class="text-muted" style="font-size: 0.7rem;">Demandes formation</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded text-center" style="background: #fff3e0;">
                                <h5 class="mb-0 fw-bold text-warning">{{ $renfFormationPendingCount }}</h5>
                                <small class="text-muted" style="font-size: 0.7rem;">En attente</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded text-center" style="background: #e8f5e9;">
                                <h5 class="mb-0 fw-bold text-success">{{ $renfDocumentsCount }}</h5>
                                <small class="text-muted" style="font-size: 0.7rem;">Docs ce mois</small>
                            </div>
                        </div>
                    </div>

                    {{-- Demandes de formation / renforcement --}}
                    <div class="p-3">
                        <h6 class="fw-bold mb-2"><i class="fas fa-file-signature me-1 text-muted"></i>Demandes de formation</h6>
                        @if($renforcementRequests->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Agent</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($renforcementRequests as $rReq)
                                            <tr>
                                                <td>
                                                    <small class="fw-bold">{{ $rReq->agent?->nom_complet ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge" style="background: #e91e63; font-size: 0.65rem;">
                                                        {{ $rReq->type === 'formation' ? 'Formation' : 'Renforcement' }}
                                                    </span>
                                                </td>
                                                <td><small>{{ Str::limit($rReq->description, 40) }}</small></td>
                                                <td>
                                                    @php
                                                        $rBadge = match($rReq->statut) {
                                                            'approuve' => 'bg-success',
                                                            'rejete' => 'bg-danger',
                                                            default => 'bg-warning',
                                                        };
                                                        $rLabel = match($rReq->statut) {
                                                            'approuve' => 'Approuve',
                                                            'rejete' => 'Rejete',
                                                            default => 'En attente',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $rBadge }}" style="font-size: 0.65rem;">{{ $rLabel }}</span>
                                                </td>
                                                <td><small class="text-muted">{{ $rReq->created_at->format('d/m/Y') }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-2">
                                <small>Aucune demande de formation.</small>
                            </div>
                        @endif
                    </div>

                    {{-- Documents parcours recents --}}
                    <div class="p-3 pt-0">
                        <h6 class="fw-bold mb-2"><i class="fas fa-certificate me-1 text-muted"></i>Documents recents (Attestations, Diplomes, Certificats)</h6>
                        @if($renforcementDocuments->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Agent</th>
                                            <th>Document</th>
                                            <th>Date d'ajout</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($renforcementDocuments as $rDoc)
                                            <tr>
                                                <td>
                                                    <small class="fw-bold">{{ $rDoc->agent?->nom_complet ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="fas fa-file-alt me-1 text-primary"></i>
                                                        {{ $rDoc->name ?? $rDoc->description ?? 'Document parcours' }}
                                                    </small>
                                                </td>
                                                <td><small class="text-muted">{{ $rDoc->created_at->format('d/m/Y') }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-2">
                                <small>Aucun document parcours enregistre.</small>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Plan de Travail Annuel --}}
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-calendar-alt me-2 text-info"></i>Plan de Travail {{ now()->year }}</h3>
                            <p class="panel-sub">Activites de votre unite organisationnelle.</p>
                        </div>
                        <a href="{{ route('plan-travail.index') }}" class="btn btn-sm btn-outline-primary">Tout voir</a>
                    </header>
                    <div class="p-3">
                        @forelse($planActivitesEnCours as $activite)
                            <a href="{{ route('plan-travail.show', $activite) }}" class="text-decoration-none d-block">
                            @php
                                $pBorder = match($activite->statut) {
                                    'terminee' => 'border-success',
                                    'en_cours' => 'border-primary',
                                    default => 'border-secondary',
                                };
                                $pBadge = match($activite->statut) {
                                    'terminee' => 'bg-success',
                                    'en_cours' => 'bg-primary',
                                    default => 'bg-secondary',
                                };
                                $pLabel = match($activite->statut) {
                                    'terminee' => 'Terminee',
                                    'en_cours' => 'En cours',
                                    default => 'Planifiee',
                                };
                            @endphp
                            <div class="border-start border-3 {{ $pBorder }} rounded p-3 mb-2" style="cursor: pointer; transition: box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div style="flex: 1;">
                                        <h6 class="mb-1 text-dark">{{ $activite->titre }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-layer-group me-1"></i>{{ $activite->trimestre ?? 'Annuel' }}
                                            @if($activite->departement) &bull; {{ $activite->departement->nom }} @endif
                                            @if($activite->province) &bull; {{ $activite->province->nom }} @endif
                                        </small>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <div class="progress flex-grow-1" style="height: 5px; max-width: 120px;">
                                                <div class="progress-bar {{ $activite->pourcentage >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $activite->pourcentage }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $activite->pourcentage }}%</small>
                                        </div>
                                    </div>
                                    <span class="badge {{ $pBadge }}">{{ $pLabel }}</span>
                                </div>
                            </div>
                            </a>
                        @empty
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block"></i>
                                <p class="mb-0">Aucune activite planifiee.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Mon parcours / Affectations --}}
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-route me-2 text-info"></i>Mon parcours</h3>
                            <p class="panel-sub">Affectations au programme.</p>
                        </div>
                    </header>
                    <div class="p-3">
                        @forelse($affectations as $aff)
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <div style="flex-shrink: 0; margin-top: 4px;">
                                    @if($aff->actif)
                                        <span class="badge bg-success" style="font-size: 0.6rem;">En cours</span>
                                    @else
                                        <span class="badge bg-secondary" style="font-size: 0.6rem;">Terminé</span>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="mb-0 text-dark" style="font-size: 0.9rem;">{{ $aff->fonction?->nom ?? 'Fonction non définie' }}</h6>
                                    <small class="text-muted">
                                        {{ $aff->niveau_administratif_label }}
                                        @if($aff->department) &mdash; {{ $aff->department->nom }} @endif
                                        @if($aff->section) / {{ $aff->section->nom }} @endif
                                        @if($aff->province) &mdash; {{ $aff->province->nom }} @endif
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        {{ $aff->date_debut?->format('d/m/Y') ?? '?' }}
                                        @if($aff->date_fin)
                                            &rarr; {{ $aff->date_fin->format('d/m/Y') }}
                                        @elseif($aff->actif)
                                            &rarr; Aujourd'hui
                                        @endif
                                    </small>
                                    @if($aff->remarque)
                                        <br><small class="text-muted fst-italic">{{ $aff->remarque }}</small>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-route fa-2x mb-2 d-block"></i>
                                <p class="mb-0">Aucune affectation enregistrée.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Communiqués SEN --}}
                <div class="dash-panel" id="communiques-sen">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-bullhorn me-2 text-danger"></i>Communiqués officiels</h3>
                            <p class="panel-sub">Annonces de la Direction / SEN.</p>
                        </div>
                    </header>
                    <div class="p-3">
                        @forelse($allCommuniques as $comm)
                            <a href="{{ route('communiques.show-public', $comm) }}" class="text-decoration-none d-block">
                            @php
                                $borderClass = match($comm->urgence) {
                                    'urgent' => 'border-danger',
                                    'important' => 'border-warning',
                                    default => 'border-info',
                                };
                                $bgClass = $comm->urgence === 'urgent' ? 'bg-light' : '';
                            @endphp
                            <div class="border-start border-3 {{ $borderClass }} {{ $bgClass }} rounded p-3 mb-2" style="cursor: pointer; transition: box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-dark">
                                            @if($comm->urgence === 'urgent')
                                                <span class="badge bg-danger me-1" style="font-size: 0.65rem;">Urgent</span>
                                            @elseif($comm->urgence === 'important')
                                                <span class="badge bg-warning text-dark me-1" style="font-size: 0.65rem;">Important</span>
                                            @endif
                                            {{ $comm->titre }}
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-pen-nib me-1"></i>{{ $comm->signataire ?? 'Direction PNMLS' }}
                                            &bull; {{ $comm->created_at->format('d/m/Y') }}
                                        </small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted mt-1"></i>
                                </div>
                                <p class="mb-0 mt-2 text-secondary">{{ Str::limit($comm->contenu, 100) }}</p>
                            </div>
                            </a>
                        @empty
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-bullhorn fa-2x mb-2 d-block"></i>
                                <p class="mb-0">Aucun communiqué.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Messages DRH --}}
                <div class="dash-panel" id="messages-drh">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-envelope me-2 text-primary"></i>Messages DRH
                                @if($unreadMessages > 0)
                                    <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">{{ $unreadMessages }} non lu{{ $unreadMessages > 1 ? 's' : '' }}</span>
                                @endif
                            </h3>
                            <p class="panel-sub">Communications de la Direction RH.</p>
                        </div>
                    </header>
                    <div class="p-3">
                        @forelse($latestMessages as $msg)
                            <a href="{{ route('messages.show', $msg) }}" class="text-decoration-none d-block">
                            <div class="border-start border-3 {{ !$msg->lu ? 'border-warning bg-light' : 'border-primary' }} rounded p-3 mb-2" style="cursor: pointer; transition: box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-dark">
                                            @if(!$msg->lu)
                                                <span class="badge bg-warning text-dark me-1" style="font-size: 0.65rem;">Nouveau</span>
                                            @endif
                                            {{ $msg->sujet }}
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>{{ $msg->sender?->name ?? 'DRH' }}
                                            &bull; {{ $msg->created_at?->format('d/m/Y a H:i') }}
                                        </small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted mt-1"></i>
                                </div>
                                <p class="mb-0 mt-2 text-secondary">{{ Str::limit($msg->contenu, 100) }}</p>
                            </div>
                            </a>
                        @empty
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                <p class="mb-0">Aucun message.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Colonne droite : Actions rapides + Profil --}}
            <div class="d-flex flex-column gap-3">
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-bolt me-2 text-warning"></i>Actions rapides</h3>
                            <p class="panel-sub">Les operations les plus utilisees.</p>
                        </div>
                    </header>
                    <div class="quick-actions">
                        <a href="{{ route('requests.create') }}" class="quick-link"><span><i class="fas fa-plane-departure"></i> Demander un conge</span><i class="fas fa-chevron-right text-muted"></i></a>
                        <a href="{{ route('documents.create') }}" class="quick-link"><span><i class="fas fa-cloud-upload-alt"></i> Televerser un document</span><i class="fas fa-chevron-right text-muted"></i></a>
                        <a href="{{ route('signalements.create') }}" class="quick-link"><span><i class="fas fa-bullhorn"></i> Faire un signalement</span><i class="fas fa-chevron-right text-muted"></i></a>
                        <a href="{{ route('profile.show', $agent) }}" class="quick-link"><span><i class="fas fa-user-cog"></i> Mettre a jour mon profil</span><i class="fas fa-chevron-right text-muted"></i></a>
                    </div>
                </div>

                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-id-badge me-2 text-success"></i>Mon profil</h3>
                            <p class="panel-sub">Informations administratives principales.</p>
                        </div>
                    </header>
                    <dl class="profile-sheet">
                        <dt>Matricule PNMLS</dt>
                        <dd>{{ $agent?->matricule_pnmls ?? 'Non renseigne' }}</dd>

                        <dt>Departement</dt>
                        <dd>{{ $agent?->departement->nom ?? 'Non assigne' }}</dd>

                        <dt>Province</dt>
                        <dd>{{ $agent?->province->nom ?? 'Non assignee' }}</dd>

                        <dt>Date d'embauche</dt>
                        <dd>{{ $agent?->date_embauche?->format('d/m/Y') ?? 'Non renseignee' }}</dd>

                        <a href="{{ route('profile.show', $agent) }}" class="btn btn-sm btn-outline-primary w-100 mt-1">Voir mon profil complet</a>
                    </dl>
                </div>
            </div>
        </section>
    </div>
</div>

{{-- Bouton flottant Webmail --}}
<a href="https://camulus.o2switch.net:2096/" target="_blank" rel="noopener noreferrer"
   class="text-decoration-none"
   style="position: fixed; bottom: 30px; right: 30px; z-index: 1050;
          background: linear-gradient(135deg, #0077B5, #005a87);
          color: #fff; border-radius: 50px; padding: 14px 24px;
          box-shadow: 0 4px 15px rgba(0,119,181,0.4);
          font-weight: 600; font-size: 0.95rem;
          transition: transform 0.2s, box-shadow 0.2s;"
   onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 20px rgba(0,119,181,0.5)'"
   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 15px rgba(0,119,181,0.4)'">
    <i class="fas fa-at me-2"></i> Webmail professionnel
</a>
@endsection
