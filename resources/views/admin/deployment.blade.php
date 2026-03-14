@extends('admin.layouts.sidebar')

@section('title', 'Déploiement')
@section('page-title', 'Déploiement Module Organe')

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-rocket text-primary me-2"></i>
                Assistant de Déploiement
            </h5>

            <p class="text-muted mb-4">
                Cette page exécute les migrations et le seeding pour activer le module Organe.
            </p>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Succès!</strong> Le module Organe est maintenant déployé et opérationnel.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error_messages') && count(session('error_messages')) > 0)
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Erreurs detectées:</strong>
                    <ul class="mb-0 ps-3 mt-2">
                        @foreach(session('error_messages') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('output_messages') && count(session('output_messages')) > 0)
                <div class="bg-dark rounded p-3 mb-4" style="font-family: 'Courier New', monospace; color: #0f0; font-size: 0.9rem; max-height: 400px; overflow-y: auto;">
                    @foreach(session('output_messages') as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            @endif

            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Étapes effectuées:</strong>
                <ol class="mb-0 ps-3 mt-2">
                    <li>Exécution des migrations (création de la table organes)</li>
                    <li>Insertion des données initiales (SEN, SEP, SEL)</li>
                    <li>Vérification de la cible</li>
                </ol>
            </div>

            <form action="{{ route('admin.deployment.deploy-organes') }}" method="POST">
                @csrf
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play me-2"></i> Lancer le Déploiement
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Retour
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-card bg-light">
            <h6 class="mb-3">
                <i class="fas fa-question-circle me-2"></i>
                Informations
            </h6>

            <div class="small">
                <p>
                    <strong>Qu'est-ce que cela fait?</strong><br>
                    Cette action crée la table de base de données pour les Organes
                    (Secrétariats Exécutifs) et y insère les 3 enregistrements de base.
                </p>

                <p>
                    <strong>Est-ce sûr?</strong><br>
                    Oui, ce script est idempotent et ne causera pas de perte de données
                    s'il est exécuté plusieurs fois.
                </p>

                <p>
                    <strong>Que se passe-t-il après?</strong><br>
                    Une fois déployé, vous pourrez:
                </p>
                <ul class="ps-3">
                    <li>Accéder au CRUD Organes</li>
                    <li>Filtrer les fonctions par organe</li>
                    <li>Voir les statistiques dans le dashboard</li>
                </ul>
            </div>
        </div>

        <div class="form-card mt-3 border-warning">
            <h6 class="mb-2 text-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Attention
            </h6>
            <small class="text-muted">
                Ne cliquez qu'une seule fois sur "Lancer le Déploiement"
                et attendez que la page se recharge.
            </small>
        </div>
    </div>
</div>

@endsection
