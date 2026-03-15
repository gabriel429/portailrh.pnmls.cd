@extends('layouts.app')

@section('title', 'Rapport Mensuel Pointages - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-chart-bar me-2"></i>Rapport mensuel pointages</h1>
                    <p class="rh-sub">Synthese d'assiduite, de presence et d'heures travaillees.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.pointages.monthly-export', ['month' => $month]) }}" class="btn-rh main"><i class="fas fa-download me-1"></i> Export Excel</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="d-flex gap-2 mb-3 flex-wrap">
            <a href="{{ route('rh.pointages.index') }}" class="btn btn-outline-secondary"><i class="fas fa-list me-2"></i>Liste</a>
            <a href="{{ route('rh.pointages.daily') }}" class="btn btn-outline-secondary"><i class="fas fa-calendar-alt me-2"></i>Par jour</a>
            <a href="{{ route('rh.pointages.monthly-report') }}" class="btn btn-primary"><i class="fas fa-chart-bar me-2"></i>Rapport mensuel</a>
        </div>

        <div class="rh-filters mb-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="month" class="form-label">Mois</label>
                    <input type="month" name="month" id="month" class="form-control" value="{{ $month }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Afficher</button>
                </div>
            </form>
        </div>

        <section class="kpi-grid">
            <article class="kpi">
                <p class="label">Total agents</p>
                <h2 class="value">{{ $globalStats['total_agents'] }}</h2>
                <span class="trend trend-info">Population suivie</span>
            </article>
            <article class="kpi">
                <p class="label">Total presents</p>
                <h2 class="value">{{ $globalStats['total_present'] }}</h2>
                <span class="trend trend-ok">Presences cumulees</span>
            </article>
            <article class="kpi">
                <p class="label">Total absents</p>
                <h2 class="value">{{ $globalStats['total_absent'] }}</h2>
                <span class="trend trend-bad">Absences cumulees</span>
            </article>
            <article class="kpi">
                <p class="label">Taux moyen</p>
                <h2 class="value">{{ $globalStats['average_attendance'] }}%</h2>
                <span class="trend trend-mid">Assiduite moyenne</span>
            </article>
        </section>

        <div class="rh-list-card p-3 p-lg-4 mb-3">
            <h5 class="mb-3"><i class="fas fa-users me-2"></i>Detail par agent - {{ $dateDebut->translatedFormat('F Y') }}</h5>
            @if($agentStats->count() > 0)
                <div class="rh-table-wrap">
                    <table class="rh-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Matricule</th>
                                <th>Jours travail</th>
                                <th>Enregistres</th>
                                <th>Presents</th>
                                <th>Absents</th>
                                <th>Heures</th>
                                <th>Taux</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agentStats as $stat)
                                <tr>
                                    <td><strong>{{ $stat['agent']->prenom }} {{ $stat['agent']->nom }}</strong></td>
                                    <td>{{ $stat['agent']->id }}</td>
                                    <td>{{ $stat['working_days'] }}</td>
                                    <td>{{ $stat['recorded'] }}</td>
                                    <td><span class="status-chip st-ok">{{ $stat['present'] }}</span></td>
                                    <td><span class="status-chip st-bad">{{ $stat['absent'] }}</span></td>
                                    <td>{{ $stat['total_hours'] }}h</td>
                                    <td>
                                        @if($stat['attendance_rate'] >= 90)
                                            <span class="status-chip st-ok">{{ $stat['attendance_rate'] }}%</span>
                                        @elseif($stat['attendance_rate'] >= 80)
                                            <span class="status-chip st-mid">{{ $stat['attendance_rate'] }}%</span>
                                        @else
                                            <span class="status-chip st-bad">{{ $stat['attendance_rate'] }}%</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong>Total</strong></td>
                                <td><strong>{{ $agentStats->sum('recorded') }}</strong></td>
                                <td><strong>{{ $globalStats['total_present'] }}</strong></td>
                                <td><strong>{{ $globalStats['total_absent'] }}</strong></td>
                                <td><strong>{{ $agentStats->sum('total_hours') }}h</strong></td>
                                <td><strong>{{ $globalStats['average_attendance'] }}%</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-chart-bar fa-4x mb-3 d-block"></i>
                    <h5>Aucune donnee</h5>
                    <p class="mb-0">Aucun pointage sur ce mois.</p>
                </div>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="rh-list-card p-3">
                    <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Periode</h6>
                    <p class="mb-1"><strong>Debut:</strong> {{ $dateDebut->format('d/m/Y') }}</p>
                    <p class="mb-1"><strong>Fin:</strong> {{ $dateFin->format('d/m/Y') }}</p>
                    <p class="mb-0"><strong>Duree:</strong> {{ $dateDebut->diffInDays($dateFin) + 1 }} jours</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="rh-list-card p-3">
                    <h6 class="mb-2"><i class="fas fa-calculator me-2"></i>Moyennes</h6>
                    <p class="mb-1"><strong>Heures moyennes/agent:</strong> {{ $globalStats['average_hours'] }}h</p>
                    <p class="mb-1"><strong>Taux d'assiduite moyen:</strong> {{ $globalStats['average_attendance'] }}%</p>
                    <p class="mb-0"><strong>Agents:</strong> {{ $globalStats['total_agents'] }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
