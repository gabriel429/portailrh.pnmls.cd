@extends('layouts.app')

@section('title', 'Dashboard RH - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
@php
    $activeRate = $totalAgents > 0 ? round(($activeAgents / $totalAgents) * 100) : 0;
    $pendingRate = $totalRequests > 0 ? round(($pendingRequests / $totalRequests) * 100) : 0;
    $approvalRate = $totalRequests > 0 ? round(($approvedRequests / $totalRequests) * 100) : 0;
@endphp

<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title">Tableau de bord RH strategique</h1>
                    <p class="rh-sub">Pilotage des effectifs, demandes et pointages en temps reel.</p>

                    {{-- Barre de recherche --}}
                    <form action="{{ route('rh.agents.index') }}" method="GET" class="mt-3">
                        <div class="input-group" style="max-width: 400px;">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher... (nom, email, matricule, province, grade, fonction, niveau etude)"
                                   value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.agents.create') }}" class="btn-rh main"><i class="fas fa-user-plus me-1"></i> Nouvel agent</a>
                        <a href="{{ route('rh.pointages.create') }}" class="btn-rh alt"><i class="fas fa-clock me-1"></i> Nouveau pointage</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="kpi-grid">
            <article class="kpi">
                <p class="label">Agents totaux</p>
                <h2 class="value">{{ $totalAgents }}</h2>
                <span class="trend trend-info"><i class="fas fa-users"></i> Effectif global</span>
            </article>
            <article class="kpi">
                <p class="label">Agents actifs</p>
                <h2 class="value">{{ $activeAgents }}</h2>
                <span class="trend {{ $activeRate >= 70 ? 'trend-ok' : 'trend-mid' }}"><i class="fas fa-chart-line"></i> {{ $activeRate }}% du total</span>
            </article>
            <article class="kpi">
                <p class="label">Demandes en attente</p>
                <h2 class="value">{{ $pendingRequests }}</h2>
                <span class="trend {{ $pendingRate <= 30 ? 'trend-ok' : 'trend-mid' }}"><i class="fas fa-hourglass-half"></i> {{ $pendingRate }}% a traiter</span>
            </article>
            <article class="kpi">
                <p class="label">Pointages enregistres</p>
                <h2 class="value">{{ $totalAttendance }}</h2>
                <span class="trend trend-info"><i class="fas fa-stopwatch"></i> Trafic global</span>
            </article>
        </section>

        <section class="overview">
            <div class="panel">
                <header class="panel-head">
                    <div>
                        <h3 class="panel-title"><i class="fas fa-file-signature me-2 text-info"></i>Demandes recentes</h3>
                        <p class="panel-sub">Les 5 dernieres demandes soumises.</p>
                    </div>
                    <a href="{{ route('requests.index') }}" class="btn btn-sm btn-outline-primary">Voir plus</a>
                </header>
                <div class="table-wrap">
                    @if($recentRequests->count() > 0)
                        <div class="table-responsive">
                            <table class="rh-table">
                                <thead>
                                    <tr>
                                        <th>Agent</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentRequests as $request)
                                        @php
                                            $statusValue = strtolower((string) $request->statut);
                                            $statusClass = str_contains($statusValue, 'approuv')
                                                ? 'st-ok'
                                                : (str_contains($statusValue, 'rejet') ? 'st-bad' : 'st-mid');
                                        @endphp
                                        <tr>
                                            <td>{{ $request->agent->prenom ?? '' }} {{ $request->agent->nom ?? '' }}</td>
                                            <td>{{ $request->type_demande ?? $request->type ?? 'N/A' }}</td>
                                            <td><span class="status-chip {{ $statusClass }}">{{ ucfirst($request->statut) }}</span></td>
                                            <td>{{ $request->created_at?->format('d/m/Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">Aucune demande recente.</div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <header class="panel-head">
                    <div>
                        <h3 class="panel-title"><i class="fas fa-sliders-h me-2 text-success"></i>Indicateurs RH</h3>
                        <p class="panel-sub">Lecture immediate de la performance.</p>
                    </div>
                </header>
                <div class="gauge-wrap">
                    <div class="gauge-item">
                        <div class="gauge-meta"><span>Taux d'agents actifs</span><span>{{ $activeRate }}%</span></div>
                        <div class="gauge-bar"><div class="gauge-fill" style="width: {{ $activeRate }}%; background: linear-gradient(90deg, #10b981, #059669);"></div></div>
                    </div>
                    <div class="gauge-item">
                        <div class="gauge-meta"><span>Taux d'approbation</span><span>{{ $approvalRate }}%</span></div>
                        <div class="gauge-bar"><div class="gauge-fill" style="width: {{ $approvalRate }}%; background: linear-gradient(90deg, #0891b2, #0e7490);"></div></div>
                    </div>
                    <div class="gauge-item">
                        <div class="gauge-meta"><span>Tickets en attente</span><span>{{ $pendingRate }}%</span></div>
                        <div class="gauge-bar"><div class="gauge-fill" style="width: {{ $pendingRate }}%; background: linear-gradient(90deg, #f59e0b, #d97706);"></div></div>
                    </div>
                    <div class="gauge-item">
                        <div class="gauge-meta"><span>Agents suspendus</span><span>{{ $suspendedAgents }}</span></div>
                        <div class="gauge-bar"><div class="gauge-fill" style="width: {{ $totalAgents > 0 ? round(($suspendedAgents / $totalAgents) * 100) : 0 }}%; background: linear-gradient(90deg, #ef4444, #dc2626);"></div></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="overview" style="grid-template-columns: 1fr 1fr;">
            <div class="panel">
                <header class="panel-head">
                    <div>
                        <h3 class="panel-title"><i class="fas fa-clock me-2 text-primary"></i>Pointages recents</h3>
                        <p class="panel-sub">Les 10 derniers enregistrements.</p>
                    </div>
                    <a href="{{ route('rh.pointages.index') }}" class="btn btn-sm btn-outline-primary">Voir plus</a>
                </header>
                <div class="table-wrap">
                    @if($recentAttendance->count() > 0)
                        <div class="table-responsive">
                            <table class="rh-table">
                                <thead>
                                    <tr>
                                        <th>Agent</th>
                                        <th>Date</th>
                                        <th>Entree</th>
                                        <th>Sortie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentAttendance as $pointage)
                                        <tr>
                                            <td>{{ $pointage->agent->prenom ?? '' }} {{ $pointage->agent->nom ?? '' }}</td>
                                            <td>{{ $pointage->date_pointage?->format('d/m/Y') }}</td>
                                            <td>
                                                @if($pointage->heure_entree)
                                                    <span class="status-chip st-ok">{{ $pointage->heure_entree }}</span>
                                                @else
                                                    <span class="status-chip st-neutral">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($pointage->heure_sortie)
                                                    <span class="status-chip st-mid">{{ $pointage->heure_sortie }}</span>
                                                @else
                                                    <span class="status-chip st-neutral">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">Aucun pointage recent.</div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <header class="panel-head">
                    <div>
                        <h3 class="panel-title"><i class="fas fa-bolt me-2 text-warning"></i>Actions rapides</h3>
                        <p class="panel-sub">Acces direct aux fonctions RH.</p>
                    </div>
                </header>
                <div class="table-wrap">
                    <div class="actions-grid">
                        <a href="{{ route('rh.agents.create') }}" class="action-card">
                            <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                            <p class="action-title">Ajouter un agent</p>
                            <p class="action-desc">Creer une nouvelle fiche agent.</p>
                        </a>
                        <a href="{{ route('rh.agents.index') }}" class="action-card">
                            <div class="action-icon"><i class="fas fa-address-book"></i></div>
                            <p class="action-title">Gestion agents</p>
                            <p class="action-desc">Consulter et modifier les profils.</p>
                        </a>
                        <a href="{{ route('requests.index') }}" class="action-card">
                            <div class="action-icon"><i class="fas fa-tasks"></i></div>
                            <p class="action-title">Traiter demandes</p>
                            <p class="action-desc">Valider ou rejeter les requetes.</p>
                        </a>
                        <a href="{{ route('rh.pointages.daily') }}" class="action-card">
                            <div class="action-icon"><i class="fas fa-calendar-day"></i></div>
                            <p class="action-title">Pointage journalier</p>
                            <p class="action-desc">Suivi quotidien des presences.</p>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
