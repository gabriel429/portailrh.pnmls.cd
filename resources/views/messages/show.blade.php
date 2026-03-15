@extends('layouts.app')

@section('title', 'Message - ' . $message->sujet)

@section('content')
<div class="container py-4" style="max-width: 800px;">

    {{-- Bouton retour --}}
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
    </a>

    {{-- Carte message avec logo PNMLS en fond --}}
    <div class="card shadow-sm border-0" style="overflow: hidden; position: relative;">

        {{-- Logo PNMLS en arriere-plan watermark --}}
        <div style="
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.06;
            pointer-events: none;
            z-index: 0;
        ">
            <img src="{{ asset('images/logo-pnmls.png') }}" alt="" style="width: 350px; height: auto;">
        </div>

        {{-- En-tete du message --}}
        <div class="card-header border-0 text-white" style="background: linear-gradient(135deg, #0077B5, #005a87); position: relative; z-index: 1;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; flex-shrink: 0;">
                    <i class="fas fa-envelope-open-text fa-lg text-white"></i>
                </div>
                <div>
                    <h4 class="mb-1">{{ $message->sujet }}</h4>
                    <small class="opacity-75">
                        <i class="fas fa-user me-1"></i> {{ $message->sender?->name ?? 'Direction RH' }}
                        &bull;
                        <i class="fas fa-calendar me-1"></i> {{ $message->created_at?->format('d/m/Y \à H:i') }}
                    </small>
                </div>
            </div>
        </div>

        {{-- Corps du message --}}
        <div class="card-body" style="position: relative; z-index: 1; min-height: 250px; padding: 2rem;">
            <div class="mb-3">
                <span class="badge {{ $message->lu ? 'bg-success' : 'bg-warning text-dark' }}">
                    <i class="fas {{ $message->lu ? 'fa-check-double' : 'fa-envelope' }} me-1"></i>
                    {{ $message->lu ? 'Lu' : 'Non lu' }}
                </span>
            </div>

            <div style="font-size: 1.05rem; line-height: 1.8; white-space: pre-wrap;">{{ $message->contenu }}</div>
        </div>

        {{-- Pied de page --}}
        <div class="card-footer bg-light border-0 text-muted small" style="position: relative; z-index: 1;">
            <div class="d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-building me-1"></i> Programme National Multisectoriel de Lutte contre le Sida
                </span>
                <span>
                    <i class="fas fa-clock me-1"></i> Reçu {{ $message->created_at?->diffForHumans() }}
                </span>
            </div>
        </div>
    </div>

</div>
@endsection
