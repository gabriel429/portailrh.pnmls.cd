@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-folder-open me-2"></i> Gestion Électronique de Documents</h2>
            <p class="text-muted mb-0">Organisez et consultez vos documents</p>
        </div>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="fas fa-cloud-upload-alt me-2"></i> Uploader un document
        </a>
    </div>

    <!-- Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('documents.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Catégorie</label>
                    <select name="categorie" class="form-select">
                        <option value="">Toutes</option>
                        <option value="identite" {{ request('categorie') === 'identite' ? 'selected' : '' }}>Identité</option>
                        <option value="parcours" {{ request('categorie') === 'parcours' ? 'selected' : '' }}>Parcours</option>
                        <option value="carriere" {{ request('categorie') === 'carriere' ? 'selected' : '' }}>Carrière</option>
                        <option value="mission" {{ request('categorie') === 'mission' ? 'selected' : '' }}>Mission</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" placeholder="Nom du document..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary flex-grow-1">
                            <i class="fas fa-search me-2"></i> Filtrer
                        </button>
                        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Onglets par catégorie -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                <i class="fas fa-file me-2"></i> Tous ({{ $documents->total() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="identite-tab" data-bs-toggle="tab" data-bs-target="#identite" type="button" role="tab" aria-controls="identite" aria-selected="false">
                <i class="fas fa-id-card me-2"></i> Identité
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="parcours-tab" data-bs-toggle="tab" data-bs-target="#parcours" type="button" role="tab" aria-controls="parcours" aria-selected="false">
                <i class="fas fa-graduation-cap me-2"></i> Parcours
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="carriere-tab" data-bs-toggle="tab" data-bs-target="#carriere" type="button" role="tab" aria-controls="carriere" aria-selected="false">
                <i class="fas fa-briefcase me-2"></i> Carrière
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission" type="button" role="tab" aria-controls="mission" aria-selected="false">
                <i class="fas fa-plane me-2"></i> Mission
            </button>
        </li>
    </ul>

    <!-- Contenu -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
            @include('documents.partials.list', ['docs' => $documents])
        </div>
    </div>

    <!-- Pagination -->
    @if($documents->count() > 0)
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Affichage {{ $documents->firstItem() }} à {{ $documents->lastItem() }}
                sur {{ $documents->total() }} documents
            </div>
            {{ $documents->links() }}
        </div>
    @endif
</div>

<style>
    .document-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .document-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    }
</style>
@endsection
