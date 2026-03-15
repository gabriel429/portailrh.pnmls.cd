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
    $pendingCount = $agent ? $agent->requests()->where('statut', 'en_attente')->count() : 0;
    $approvedCount = $agent ? $agent->requests()->where('statut', 'approuvé')->count() : 0;
    $absenceCount = $agent ? $agent->pointages()->whereNull('heure_entree')->count() : 0;
    $latestRequests = $agent ? $agent->requests()->latest()->limit(5)->get() : collect();

    // Messages du DRH
    $unreadMessages = $agent ? $agent->messages()->where('lu', false)->count() : 0;
    $latestMessages = $agent ? $agent->messages()->with('sender')->latest()->limit(5)->get() : collect();
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
            <article class="kpi">
                <p class="label">Documents</p>
                <h2 class="value">{{ $agent?->documents->count() ?? 0 }}</h2>
                <span class="trend trend-info"><i class="fas fa-folder-open"></i> Dossier personnel</span>
            </article>
            <article class="kpi">
                <p class="label">Demandes en attente</p>
                <h2 class="value">{{ $pendingCount }}</h2>
                <span class="trend trend-mid"><i class="fas fa-hourglass-half"></i> Suivi en cours</span>
            </article>
            <article class="kpi">
                <p class="label">Demandes approuvees</p>
                <h2 class="value">{{ $approvedCount }}</h2>
                <span class="trend trend-ok"><i class="fas fa-check-circle"></i> Historique valide</span>
            </article>
            <article class="kpi">
                <p class="label">Absences detectees</p>
                <h2 class="value">{{ $absenceCount }}</h2>
                <span class="trend trend-bad"><i class="fas fa-calendar-times"></i> Controle presence</span>
            </article>
            <article class="kpi">
                <p class="label">Messages non lus</p>
                <h2 class="value">{{ $unreadMessages }}</h2>
                <span class="trend {{ $unreadMessages > 0 ? 'trend-mid' : 'trend-ok' }}"><i class="fas fa-envelope"></i> Messagerie DRH</span>
            </article>
        </section>

        <section class="dash-grid">
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

        {{-- Section Messages DRH --}}
        @if($latestMessages->count() > 0)
        <section class="overview" style="margin-top: 20px;">
            <div class="panel">
                <header class="panel-head">
                    <div>
                        <h3 class="panel-title"><i class="fas fa-envelope me-2 text-primary"></i>Messages de la DRH
                            @if($unreadMessages > 0)
                                <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">{{ $unreadMessages }} non lu{{ $unreadMessages > 1 ? 's' : '' }}</span>
                            @endif
                        </h3>
                        <p class="panel-sub">Communications de la Direction des Ressources Humaines.</p>
                    </div>
                </header>
                <div class="p-3">
                    @foreach($latestMessages as $msg)
                        <a href="{{ route('messages.show', $msg) }}" class="text-decoration-none d-block">
                        <div class="border-start border-3 {{ !$msg->lu ? 'border-warning bg-light' : 'border-primary' }} rounded p-3 mb-3" style="cursor: pointer; transition: box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
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
                    @endforeach
                </div>
            </div>
        </section>
        @endif
    </div>
</div>
@endsection
