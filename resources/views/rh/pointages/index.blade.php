@extends('layouts.app')

@section('title', 'Pointages RH - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
@php /** @var \Illuminate\Pagination\LengthAwarePaginator $pointages */ @endphp
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-clock me-2"></i>Gestion des pointages</h1>
                    <p class="rh-sub">Suivi des presences, absences et heures travaillees.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.pointages.create') }}" class="btn-rh main"><i class="fas fa-plus me-1"></i> Nouveau pointage</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="d-flex gap-2 mb-3 flex-wrap">
            <a href="{{ route('rh.pointages.index') }}" class="btn btn-primary"><i class="fas fa-list me-2"></i>Liste</a>
            <a href="{{ route('rh.pointages.daily') }}" class="btn btn-outline-secondary"><i class="fas fa-calendar-alt me-2"></i>Par jour</a>
            <a href="{{ route('rh.pointages.monthly-report') }}" class="btn btn-outline-secondary"><i class="fas fa-chart-bar me-2"></i>Rapport mensuel</a>
        </div>

        <div class="rh-list-card p-3 p-lg-4">
            @if($pointages->count() > 0)
                <div class="rh-table-wrap">
                    <table class="rh-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Matricule</th>
                                <th>Date</th>
                                <th>Entree</th>
                                <th>Sortie</th>
                                <th>Heures</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pointages as $pointage)
                                <tr>
                                    <td><strong>{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</strong></td>
                                    <td>{{ $pointage->agent->id_agent }}</td>
                                    <td>{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }}</td>
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
                                    <td>
                                        @if($pointage->heures_travaillees)
                                            <strong>{{ $pointage->heures_travaillees }}h</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('rh.pointages.show', $pointage) }}" class="btn btn-outline-primary" title="Details"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('rh.pointages.edit', $pointage) }}" class="btn btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                            <form method="POST" action="{{ route('rh.pointages.destroy', $pointage) }}" style="display:inline;" onsubmit="return confirm('Etes-vous sur ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                    <div class="text-muted small">
                        Affichage {{ $pointages->firstItem() ?? 0 }} a {{ $pointages->lastItem() ?? 0 }} sur {{ $pointages->total() }} pointages
                    </div>
                    {{ $pointages->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-clock fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucun pointage</h5>
                    <p class="text-muted">Il n'y a aucun pointage enregistre.</p>
                    <a href="{{ route('rh.pointages.create') }}" class="btn btn-primary mt-2"><i class="fas fa-plus me-2"></i>Creer un pointage</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
