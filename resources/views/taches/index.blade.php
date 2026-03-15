@extends('layouts.app')

@section('title', 'Mes Taches - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>Mes Taches</h1>
                    <p class="rh-sub">Suivi des taches assignees et creees.</p>
                </div>
                @if($isDirecteur)
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('taches.create') }}" class="btn-rh main">
                            <i class="fas fa-plus-circle me-1"></i> Nouvelle tache
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Taches assignees a moi --}}
        <div class="dash-panel mt-3">
            <header class="panel-head">
                <div>
                    <h3 class="panel-title"><i class="fas fa-clipboard-check me-2 text-primary"></i>Taches qui me sont assignees</h3>
                    <p class="panel-sub">Taches attribuees par votre directeur.</p>
                </div>
            </header>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>De</th>
                            <th>Priorite</th>
                            <th>Statut</th>
                            <th>Echeance</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mesTaches as $tache)
                            <tr>
                                <td>
                                    <strong>{{ $tache->titre }}</strong>
                                    @if($tache->description)
                                        <br><small class="text-muted">{{ Str::limit($tache->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>{{ $tache->createur?->nom_complet ?? '-' }}</td>
                                <td>
                                    @if($tache->priorite === 'urgente')
                                        <span class="badge bg-danger">Urgente</span>
                                    @elseif($tache->priorite === 'haute')
                                        <span class="badge bg-warning text-dark">Haute</span>
                                    @else
                                        <span class="badge bg-secondary">Normale</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tache->statut === 'terminee')
                                        <span class="badge bg-success">Terminee</span>
                                    @elseif($tache->statut === 'en_cours')
                                        <span class="badge bg-primary">En cours</span>
                                    @else
                                        <span class="badge bg-secondary">Nouvelle</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tache->date_echeance)
                                        {{ $tache->date_echeance->format('d/m/Y') }}
                                        @if($tache->date_echeance->isPast() && $tache->statut !== 'terminee')
                                            <br><small class="text-danger">En retard</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $tache->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('taches.show', $tache) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Aucune tache assignee.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Taches creees par moi (Directeur) --}}
        @if($isDirecteur)
        <div class="dash-panel mt-3">
            <header class="panel-head">
                <div>
                    <h3 class="panel-title"><i class="fas fa-clipboard-list me-2 text-success"></i>Taches que j'ai assignees</h3>
                    <p class="panel-sub">Suivi des taches attribuees aux agents de votre departement.</p>
                </div>
            </header>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Assigne a</th>
                            <th>Priorite</th>
                            <th>Statut</th>
                            <th>Echeance</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tachesCreees as $tache)
                            <tr>
                                <td>
                                    <strong>{{ $tache->titre }}</strong>
                                    @if($tache->description)
                                        <br><small class="text-muted">{{ Str::limit($tache->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>{{ $tache->agent?->nom_complet ?? '-' }}</td>
                                <td>
                                    @if($tache->priorite === 'urgente')
                                        <span class="badge bg-danger">Urgente</span>
                                    @elseif($tache->priorite === 'haute')
                                        <span class="badge bg-warning text-dark">Haute</span>
                                    @else
                                        <span class="badge bg-secondary">Normale</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tache->statut === 'terminee')
                                        <span class="badge bg-success">Terminee</span>
                                    @elseif($tache->statut === 'en_cours')
                                        <span class="badge bg-primary">En cours</span>
                                    @else
                                        <span class="badge bg-secondary">Nouvelle</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tache->date_echeance)
                                        {{ $tache->date_echeance->format('d/m/Y') }}
                                        @if($tache->date_echeance->isPast() && $tache->statut !== 'terminee')
                                            <br><small class="text-danger">En retard</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $tache->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('taches.show', $tache) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Aucune tache creee.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
