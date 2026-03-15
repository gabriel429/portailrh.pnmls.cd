@extends('layouts.app')

@section('title', 'Communiqué - ' . $communique->titre)

@section('content')
<div class="container py-4" style="max-width: 800px;">

    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
    </a>

    <div class="card shadow-sm border-0" style="overflow: hidden; position: relative;">

        {{-- Logo PNMLS en watermark --}}
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.06; pointer-events: none; z-index: 0;">
            <img src="{{ asset('images/logo-pnmls.png') }}" alt="" style="width: 350px; height: auto;">
        </div>

        {{-- En-tete --}}
        @php
            $headerBg = match($communique->urgence) {
                'urgent' => 'linear-gradient(135deg, #dc3545, #a71d2a)',
                'important' => 'linear-gradient(135deg, #ffc107, #e0a800)',
                default => 'linear-gradient(135deg, #0077B5, #005a87)',
            };
            $headerText = $communique->urgence === 'important' ? 'text-dark' : 'text-white';
        @endphp
        <div class="card-header border-0 {{ $headerText }}" style="background: {{ $headerBg }}; position: relative; z-index: 1;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; flex-shrink: 0;">
                    @if($communique->urgence === 'urgent')
                        <i class="fas fa-exclamation-triangle fa-lg {{ $headerText }}"></i>
                    @elseif($communique->urgence === 'important')
                        <i class="fas fa-exclamation-circle fa-lg {{ $headerText }}"></i>
                    @else
                        <i class="fas fa-bullhorn fa-lg {{ $headerText }}"></i>
                    @endif
                </div>
                <div>
                    <div class="mb-1">
                        @if($communique->urgence === 'urgent')
                            <span class="badge bg-light text-danger me-1">URGENT</span>
                        @elseif($communique->urgence === 'important')
                            <span class="badge bg-dark me-1">IMPORTANT</span>
                        @endif
                    </div>
                    <h4 class="mb-1">{{ $communique->titre }}</h4>
                    <small class="opacity-75">
                        <i class="fas fa-pen-nib me-1"></i> {{ $communique->signataire ?? 'Direction PNMLS' }}
                        &bull;
                        <i class="fas fa-calendar me-1"></i> {{ $communique->created_at?->format('d/m/Y \à H:i') }}
                    </small>
                </div>
            </div>
        </div>

        {{-- Corps --}}
        <div class="card-body" style="position: relative; z-index: 1; min-height: 250px; padding: 2rem;">
            <div style="font-size: 1.05rem; line-height: 1.8; white-space: pre-wrap;">{{ $communique->contenu }}</div>
        </div>

        {{-- Pied de page --}}
        <div class="card-footer bg-light border-0 text-muted small" style="position: relative; z-index: 1;">
            <div class="d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-building me-1"></i> Programme National Multisectoriel de Lutte contre le Sida
                </span>
                <span>
                    <i class="fas fa-clock me-1"></i> Publié {{ $communique->created_at?->diffForHumans() }}
                    @if($communique->date_expiration)
                        &bull; Expire le {{ $communique->date_expiration->format('d/m/Y') }}
                    @endif
                </span>
            </div>
        </div>
    </div>

</div>
@endsection
