@extends('layouts.app')

@section('title', 'Detail Pointage - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-file-alt me-2"></i>Details du pointage</h1>
                    <p class="rh-sub">{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }} - {{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.pointages.edit', $pointage) }}" class="btn-rh main"><i class="fas fa-edit me-1"></i> Modifier</a>
                        <a href="{{ route('rh.pointages.index') }}" class="btn-rh alt"><i class="fas fa-arrow-left me-1"></i> Retour</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="rh-list-card p-3 p-lg-4 mb-3">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Informations pointage</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted">Date</small>
                            <p class="mb-0 fw-bold">{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Entree</small>
                            <p class="mb-0">{{ $pointage->heure_entree ?? 'Non enregistree' }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Sortie</small>
                            <p class="mb-0">{{ $pointage->heure_sortie ?? 'Non enregistree' }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Heures travaillees</small>
                            <p class="mb-0">{{ $pointage->heures_travaillees ? $pointage->heures_travaillees . ' heures' : '-' }}</p>
                        </div>
                    </div>
                </div>

                @if($pointage->observations)
                    <div class="rh-list-card p-3 p-lg-4">
                        <h5 class="mb-2"><i class="fas fa-sticky-note me-2"></i>Observations</h5>
                        <p class="mb-0">{{ $pointage->observations }}</p>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="rh-list-card p-3 mb-3">
                    <h6 class="mb-2"><i class="fas fa-user me-2"></i>Agent</h6>
                    <p class="mb-1"><strong>{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</strong></p>
                    <p class="mb-1"><small class="text-muted">ID:</small> {{ $pointage->agent->id_agent }}</p>
                    <p class="mb-0"><small class="text-muted">Email:</small> {{ $pointage->agent->email }}</p>
                </div>

                <div class="rh-list-card p-3 mb-3">
                    <h6 class="mb-2"><i class="fas fa-chart-bar me-2"></i>Resume</h6>
                    <p class="mb-1"><small class="text-muted">ID:</small> #{{ $pointage->id }}</p>
                    <p class="mb-1"><small class="text-muted">Cree le:</small> {{ $pointage->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="mb-0"><small class="text-muted">Maj:</small> {{ $pointage->updated_at?->format('d/m/Y H:i') }}</p>
                </div>

                <div class="rh-list-card p-3">
                    <h6 class="mb-2"><i class="fas fa-cog me-2"></i>Actions</h6>
                    <form method="POST" action="{{ route('rh.pointages.destroy', $pointage) }}" onsubmit="return confirm('Etes-vous sur de supprimer ce pointage ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 btn-sm"><i class="fas fa-trash me-2"></i>Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
