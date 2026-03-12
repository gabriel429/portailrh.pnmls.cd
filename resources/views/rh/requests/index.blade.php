@extends('layouts.app')

@section('title', 'Demandes RH - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>Gestion des demandes</h1>
                    <p class="rh-sub">Suivi, validation et historisation des demandes des agents.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('requests.create') }}" class="btn-rh main"><i class="fas fa-plus me-1"></i> Nouvelle demande</a>
                    </div>
                </div>
            </div>
        </section>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Erreur</strong>
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

        <div class="rh-toolbar">
            <div class="rh-filters">
                <form method="GET" action="{{ route('requests.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="">Tous</option>
                            <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                            <option value="approuvé" {{ request('statut') === 'approuvé' ? 'selected' : '' }}>Approuve</option>
                            <option value="rejeté" {{ request('statut') === 'rejeté' ? 'selected' : '' }}>Rejete</option>
                            <option value="annulé" {{ request('statut') === 'annulé' ? 'selected' : '' }}>Annule</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">Tous</option>
                            <option value="congé" {{ request('type') === 'congé' ? 'selected' : '' }}>Conge</option>
                            <option value="absence" {{ request('type') === 'absence' ? 'selected' : '' }}>Absence</option>
                            <option value="permission" {{ request('type') === 'permission' ? 'selected' : '' }}>Permission</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-filter me-2"></i> Filtrer
                        </button>
                        <a href="{{ route('requests.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-redo me-2"></i> Reinitialiser
                        </a>
                    </div>
                </form>
            </div>
            <div class="text-muted small d-none d-lg-block">
                Total visible: {{ $requests->total() }}
            </div>
        </div>

        <div class="rh-list-card p-3 p-lg-4">
            @if($requests->count() > 0)
                <div class="rh-table-wrap">
                    <table class="rh-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Type</th>
                                <th>Periode</th>
                                <th>Statut</th>
                                <th>Date creation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)
                                <tr>
                                    <td>
                                        <strong>{{ $request->agent->prenom }} {{ $request->agent->nom }}</strong><br>
                                        <small class="text-muted">{{ $request->agent->matricule_pnmls }}</small>
                                    </td>
                                    <td>
                                        <span class="rh-pill st-mid">{{ ucfirst($request->type) }}</span>
                                    </td>
                                    <td>
                                        <small>
                                            {{ $request->date_debut->format('d/m/Y') }}
                                            @if($request->date_fin)
                                                a {{ $request->date_fin->format('d/m/Y') }}
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        @switch($request->statut)
                                            @case('en_attente')
                                                <span class="rh-pill st-mid">En attente</span>
                                                @break
                                            @case('approuvé')
                                                <span class="rh-pill st-ok">Approuve</span>
                                                @break
                                            @case('rejeté')
                                                <span class="rh-pill st-bad">Rejete</span>
                                                @break
                                            @case('annulé')
                                                <span class="rh-pill st-neutral">Annule</span>
                                                @break
                                            @default
                                                <span class="rh-pill st-neutral">{{ ucfirst($request->statut) }}</span>
                                        @endswitch
                                    </td>
                                    <td><small>{{ $request->created_at->format('d/m/Y H:i') }}</small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('requests.show', $request) }}" class="btn btn-outline-primary" title="Details">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                    <div class="text-muted small">
                        Affichage {{ $requests->firstItem() ?? 0 }} a {{ $requests->lastItem() ?? 0 }} sur {{ $requests->total() }} demandes
                    </div>
                    {{ $requests->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucune demande</h5>
                    <p class="text-muted">Il n'y a aucune demande a afficher.</p>
                    <a href="{{ route('requests.create') }}" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-2"></i> Creer une demande
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
