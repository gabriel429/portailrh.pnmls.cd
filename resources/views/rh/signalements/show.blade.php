@extends('layouts.app')

@section('title', 'Detail Signalement - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-file-circle-exclamation me-2"></i>Detail du signalement</h1>
                    <p class="rh-sub">Dossier #{{ $signalement->id }} | {{ ucfirst($signalement->type) }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('signalements.edit', $signalement) }}" class="btn-rh main"><i class="fas fa-edit me-1"></i> Modifier</a>
                        <a href="{{ route('signalements.index') }}" class="btn-rh alt"><i class="fas fa-arrow-left me-1"></i> Retour</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="rh-list-card p-3 p-lg-4 mb-3">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Informations incident</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted">Type</small>
                            <p class="mb-0 fw-bold">{{ ucfirst($signalement->type) }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Severite</small>
                            <p class="mb-0">{{ ucfirst($signalement->severite) }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Statut</small>
                            <p class="mb-0">{{ ucfirst($signalement->statut) }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Date creation</small>
                            <p class="mb-0">{{ $signalement->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Description</small>
                            <p class="mb-0">{{ $signalement->description }}</p>
                        </div>
                    </div>
                </div>

                @if($signalement->observations)
                    <div class="rh-list-card p-3 p-lg-4">
                        <h5 class="mb-2"><i class="fas fa-sticky-note me-2"></i>Observations</h5>
                        <p class="mb-0">{{ $signalement->observations }}</p>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="rh-list-card p-3 mb-3">
                    <h6 class="mb-2"><i class="fas fa-user me-2"></i>Agent concerne</h6>
                    <p class="mb-1"><strong>{{ $signalement->agent->prenom ?? '' }} {{ $signalement->agent->nom ?? '' }}</strong></p>
                    <p class="mb-1"><small class="text-muted">ID:</small> {{ $signalement->agent->id_agent }}</p>
                    <p class="mb-0"><small class="text-muted">Email:</small> {{ $signalement->agent->email ?? 'N/A' }}</p>
                </div>

                <div class="rh-list-card p-3">
                    <h6 class="mb-2"><i class="fas fa-cog me-2"></i>Actions</h6>
                    <form method="POST" action="{{ route('signalements.destroy', $signalement) }}" onsubmit="return confirm('Etes-vous sur de supprimer ce signalement ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm w-100"><i class="fas fa-trash me-2"></i>Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
