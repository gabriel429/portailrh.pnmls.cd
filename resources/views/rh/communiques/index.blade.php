@extends('layouts.app')

@section('title', 'Communiqués - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-bullhorn me-2"></i>Communiqués Officiels</h1>
                    <p class="rh-sub">Gestion des annonces diffusées à tous les agents.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('rh.communiques.create') }}" class="btn-rh main">
                            <i class="fas fa-plus-circle me-1"></i> Nouveau communiqué
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="dash-panel mt-3">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Urgence</th>
                            <th>Signataire</th>
                            <th>Date</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($communiques as $communique)
                            <tr>
                                <td>
                                    <strong>{{ $communique->titre }}</strong>
                                    <br><small class="text-muted">{{ Str::limit($communique->contenu, 60) }}</small>
                                </td>
                                <td>
                                    @if($communique->urgence === 'urgent')
                                        <span class="badge bg-danger">Urgent</span>
                                    @elseif($communique->urgence === 'important')
                                        <span class="badge bg-warning text-dark">Important</span>
                                    @else
                                        <span class="badge bg-info text-white">Normal</span>
                                    @endif
                                </td>
                                <td>{{ $communique->signataire ?? '-' }}</td>
                                <td>{{ $communique->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($communique->date_expiration)
                                        {{ $communique->date_expiration->format('d/m/Y') }}
                                        @if($communique->date_expiration->isPast())
                                            <br><small class="text-danger">Expiré</small>
                                        @endif
                                    @else
                                        <span class="text-muted">Illimité</span>
                                    @endif
                                </td>
                                <td>
                                    @if($communique->actif)
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('rh.communiques.edit', $communique) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('rh.communiques.destroy', $communique) }}" method="POST" onsubmit="return confirm('Supprimer ce communiqué ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Aucun communiqué publié.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($communiques->hasPages())
                <div class="p-3">
                    {{ $communiques->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
