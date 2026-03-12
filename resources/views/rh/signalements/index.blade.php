@extends('layouts.app')

@section('title', 'Signalements RH - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
@php /** @var \Illuminate\Pagination\LengthAwarePaginator $signalements */ @endphp
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-triangle-exclamation me-2"></i>Gestion des signalements</h1>
                    <p class="rh-sub">Suivi des incidents, severite et statut de resolution.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('signalements.create') }}" class="btn-rh main"><i class="fas fa-plus me-1"></i> Nouveau signalement</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="rh-list-card p-3 p-lg-4">
            @if($signalements->count() > 0)
                <div class="rh-table-wrap">
                    <table class="rh-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Type</th>
                                <th>Severite</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($signalements as $signalement)
                                <tr>
                                    <td>
                                        <strong>{{ $signalement->agent->prenom ?? '' }} {{ $signalement->agent->nom ?? '' }}</strong><br>
                                        <small class="text-muted">{{ $signalement->agent->matricule_pnmls ?? 'N/A' }}</small>
                                    </td>
                                    <td>{{ ucfirst($signalement->type) }}</td>
                                    <td>
                                        @if($signalement->severite === 'haute')
                                            <span class="status-chip st-bad">Haute</span>
                                        @elseif($signalement->severite === 'moyenne')
                                            <span class="status-chip st-mid">Moyenne</span>
                                        @else
                                            <span class="status-chip st-ok">Basse</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($signalement->statut === 'ouvert')
                                            <span class="status-chip st-bad">Ouvert</span>
                                        @elseif($signalement->statut === 'en_cours')
                                            <span class="status-chip st-mid">En cours</span>
                                        @elseif($signalement->statut === 'résolu')
                                            <span class="status-chip st-ok">Resolu</span>
                                        @elseif($signalement->statut === 'fermé')
                                            <span class="status-chip st-neutral">Ferme</span>
                                        @else
                                            <span class="status-chip st-neutral">{{ ucfirst($signalement->statut) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $signalement->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('signalements.show', $signalement) }}" class="btn btn-outline-primary" title="Details"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('signalements.edit', $signalement) }}" class="btn btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                            <form method="POST" action="{{ route('signalements.destroy', $signalement) }}" style="display:inline;" onsubmit="return confirm('Etes-vous sur ?');">
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
                        Affichage {{ $signalements->firstItem() ?? 0 }} a {{ $signalements->lastItem() ?? 0 }} sur {{ $signalements->total() }} signalements
                    </div>
                    {{ $signalements->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-triangle-exclamation fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucun signalement</h5>
                    <p class="text-muted">Aucun incident n'a encore ete signale.</p>
                    <a href="{{ route('signalements.create') }}" class="btn btn-primary mt-2"><i class="fas fa-plus me-2"></i>Creer un signalement</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
