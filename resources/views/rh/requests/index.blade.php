@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-tasks me-2"></i> Gestion des Demandes</h2>
            <p class="text-muted mb-0">Suivi et validation des demandes d'agents</p>
        </div>
        <a href="{{ route('requests.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Nouvelle demande
        </a>
    </div>

    <!-- Messages -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erreur !</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4 collapse-section">
        <div class="card-body">
            <form method="GET" action="{{ route('requests.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="">Tous</option>
                        <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                        <option value="approuvé" {{ request('statut') === 'approuvé' ? 'selected' : '' }}>Approuvé</option>
                        <option value="rejeté" {{ request('statut') === 'rejeté' ? 'selected' : '' }}>Rejeté</option>
                        <option value="annulé" {{ request('statut') === 'annulé' ? 'selected' : '' }}>Annulé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">Tous</option>
                        <option value="congé" {{ request('type') === 'congé' ? 'selected' : '' }}>Congé</option>
                        <option value="absence" {{ request('type') === 'absence' ? 'selected' : '' }}>Absence</option>
                        <option value="permission" {{ request('type') === 'permission' ? 'selected' : '' }}>Permission</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-filter me-2"></i> Filtrer
                    </button>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <a href="{{ route('requests.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-redo me-2"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des demandes -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($requests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">Agent</th>
                                <th style="width: 12%;">Type</th>
                                <th style="width: 15%;">Période</th>
                                <th style="width: 15%;">Statut</th>
                                <th style="width: 15%;">Date création</th>
                                <th style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $request)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <strong>{{ $request->agent->prenom }} {{ $request->agent->nom }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $request->agent->matricule_pnmls }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($request->type) }}</span>
                                    </td>
                                    <td>
                                        <small>
                                            {{ $request->date_debut->format('d/m/Y') }}
                                            @if($request->date_fin)
                                                à {{ $request->date_fin->format('d/m/Y') }}
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        @switch($request->statut)
                                            @case('en_attente')
                                                <span class="badge bg-warning text-dark">En attente</span>
                                                @break
                                            @case('approuvé')
                                                <span class="badge bg-success">Approuvé</span>
                                                @break
                                            @case('rejeté')
                                                <span class="badge bg-danger">Rejeté</span>
                                                @break
                                            @case('annulé')
                                                <span class="badge bg-secondary">Annulé</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <small>{{ $request->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('requests.show', $request) }}" class="btn btn-outline-primary" title="Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('requests.edit', $request) }}" class="btn btn-outline-warning" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('requests.destroy', $request) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-2 d-block"></i>
                                        <p class="text-muted mb-0">Aucune demande trouvée</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Affichage {{ $requests->firstItem() ?? 0 }} à {{ $requests->lastItem() ?? 0 }}
                        sur {{ $requests->total() }} demandes
                    </div>
                    {{ $requests->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-5x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucune demande</h5>
                    <p class="text-muted">Il n'y a aucune demande à afficher</p>
                    <a href="{{ route('requests.create') }}" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-2"></i> Créer une demande
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .collapse-section .card-body {
        background-color: #f8f9fa;
    }
</style>
@endsection
