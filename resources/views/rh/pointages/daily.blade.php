@extends('layouts.app')

@section('title', 'Pointages Par Jour - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
@php /** @var \Illuminate\Support\Collection $agents */ @endphp
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-calendar-alt me-2"></i>Pointages par jour</h1>
                    <p class="rh-sub">Analyse quotidienne des presences sur une periode donnee.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.pointages.daily-export', ['date_debut' => $dateDebut, 'date_fin' => $dateFin, 'agent_id' => $agent_id]) }}" class="btn-rh main"><i class="fas fa-download me-1"></i> Export Excel</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="d-flex gap-2 mb-3 flex-wrap">
            <a href="{{ route('rh.pointages.index') }}" class="btn btn-outline-secondary"><i class="fas fa-list me-2"></i>Liste</a>
            <a href="{{ route('rh.pointages.daily') }}" class="btn btn-primary"><i class="fas fa-calendar-alt me-2"></i>Par jour</a>
            <a href="{{ route('rh.pointages.monthly-report') }}" class="btn btn-outline-secondary"><i class="fas fa-chart-bar me-2"></i>Rapport mensuel</a>
        </div>

        <div class="rh-filters mb-3">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date debut</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $dateDebut }}">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date fin</label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $dateFin }}">
                </div>
                <div class="col-md-4">
                    <label for="agent_id" class="form-label">Agent</label>
                    <select name="agent_id" id="agent_id" class="form-select">
                        <option value="">Tous les agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ $agent_id == $agent->id ? 'selected' : '' }}>
                                {{ $agent->prenom }} {{ $agent->nom }} (ID: {{ $agent->id }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Filtrer</button>
                </div>
            </form>
        </div>

        @if($pointagesByDate->count() > 0)
            @foreach($pointagesByDate as $date => $dayPointages)
                @php
                    $stats = $dailyStats[$date];
                    $dateObj = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
                @endphp
                <div class="rh-list-card p-3 p-lg-4 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>{{ $dateObj->translatedFormat('l d F Y') }}</h5>
                        <div class="d-flex gap-2">
                            <span class="rh-pill st-neutral">{{ $stats['count'] }} agents</span>
                            <span class="rh-pill st-ok">{{ $stats['present'] }} presents</span>
                            <span class="rh-pill st-bad">{{ $stats['absent'] }} absents</span>
                        </div>
                    </div>
                    <div class="rh-table-wrap">
                        <table class="rh-table">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Matricule</th>
                                    <th>Entree</th>
                                    <th>Sortie</th>
                                    <th>Heures</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dayPointages as $pointage)
                                    <tr>
                                        <td><strong>{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</strong></td>
                                        <td>{{ $pointage->agent->id }}</td>
                                        <td>{{ $pointage->heure_entree ? $pointage->heure_entree : '-' }}</td>
                                        <td>{{ $pointage->heure_sortie ? $pointage->heure_sortie : '-' }}</td>
                                        <td>{{ $pointage->heures_travaillees ? $pointage->heures_travaillees . 'h' : '-' }}</td>
                                        <td>
                                            @if($pointage->heure_entree)
                                                <span class="status-chip st-ok">Present</span>
                                            @else
                                                <span class="status-chip st-bad">Absent</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($stats['total_hours'] > 0)
                        <div class="text-end text-muted small mt-2"><strong>Total heures:</strong> {{ $stats['total_hours'] }}h</div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="rh-list-card p-5 text-center">
                <i class="fas fa-calendar-alt fa-4x text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Aucun pointage</h5>
                <p class="text-muted">Aucune donnee pour la periode selectionnee.</p>
            </div>
        @endif
    </div>
</div>
@endsection
