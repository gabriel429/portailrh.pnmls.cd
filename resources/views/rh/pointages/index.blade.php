@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-clock me-2"></i> Gestion des Pointages</h2>
            <p class="text-muted mb-0">Enregistrement des présences et absences</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <a href="{{ route('rh.pointages.index') }}" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i> Liste
                </a>
                <a href="{{ route('rh.pointages.daily') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-calendar-alt me-2"></i> Par Jour
                </a>
                <a href="{{ route('rh.pointages.monthly-report') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-chart-bar me-2"></i> Rapport Mensuel
                </a>
            </div>
            <a href="{{ route('rh.pointages.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-2"></i> Nouveau pointage
            </a>
        </div>
    </div>

    <!-- Tableau des pointages -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($pointages->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Agent</th>
                                <th>Matricule</th>
                                <th>Date</th>
                                <th>Entrée</th>
                                <th>Sortie</th>
                                <th>Heures</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pointages as $pointage)
                                <tr>
                                    <td>
                                        <strong>{{ $pointage->agent->prenom }} {{ $pointage->agent->nom }}</strong>
                                    </td>
                                    <td>{{ $pointage->agent->matricule_pnmls }}</td>
                                    <td>{{ $pointage->date_pointage?->format('d/m/Y') ?? 'N/A' }}</td>
                                    <td>
                                        @if($pointage->heure_entree)
                                            <span class="badge bg-success">{{ $pointage->heure_entree }}</span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pointage->heure_sortie)
                                            <span class="badge bg-info">{{ $pointage->heure_sortie }}</span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
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
                                            <a href="{{ route('rh.pointages.show', $pointage) }}" class="btn btn-outline-primary" title="Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('rh.pointages.edit', $pointage) }}" class="btn btn-outline-warning" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('rh.pointages.destroy', $pointage) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Affichage {{ $pointages->firstItem() ?? 0 }} à {{ $pointages->lastItem() ?? 0 }}
                        sur {{ $pointages->total() }} pointages
                    </div>
                    {{ $pointages->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-clock fa-5x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucun pointage</h5>
                    <p class="text-muted">Il n'y a aucun pointage enregistré</p>
                    <a href="{{ route('rh.pointages.create') }}" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-2"></i> Créer un pointage
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
