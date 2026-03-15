@extends('admin.layouts.sidebar')

@section('title', 'Déploiement')
@section('page-title', 'Assistant de Déploiement')

@section('content')

<div class="row">
    {{-- Module Organes --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-sitemap text-primary me-2"></i>
                Déploiement Module Organe
            </h5>

            <p class="text-muted mb-3">
                Déployer la table des Organes (SEN, SEP, SEL)
            </p>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Succès!</strong> Le module Organe est maintenant déployé et opérationnel.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error_messages') && count(session('error_messages')) > 0)
                <div class="alert alert-danger alert-dismissible fade show mb-3">
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
                <div class="bg-dark rounded p-3 mb-3" style="font-family: 'Courier New', monospace; color: #0f0; font-size: 0.85rem; max-height: 300px; overflow-y: auto;">
                    @foreach(session('output_messages') as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.deployment.deploy-organes') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-play me-2"></i> Lancer Organes
                </button>
            </form>
        </div>
    </div>

    {{-- Module Utilisateurs --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-users-cog text-success me-2"></i>
                Déploiement Système Utilisateurs
            </h5>

            <p class="text-muted mb-3">
                Ajouter les colonnes agent_id et role_id à la table users
            </p>

            <form action="{{ route('admin.deployment.deploy-users') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-play me-2"></i> Lancer Utilisateurs
                </button>
            </form>
        </div>
    </div>

    {{-- Module Institutions --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-building text-info me-2"></i>
                Déploiement Module Institutions
            </h5>

            <p class="text-muted mb-3">
                Crée les tables institutions (11 catégories + ~70 institutions)
            </p>

            <form action="{{ route('admin.deployment.deploy-institutions') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-info text-white">
                    <i class="fas fa-play me-2"></i> Lancer Institutions
                </button>
            </form>
        </div>
    </div>

    {{-- Module Messages --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-envelope text-warning me-2"></i>
                Deploiement Module Messages
            </h5>

            <p class="text-muted mb-3">
                Cree la table messages (messagerie DRH vers agents)
            </p>

            <form action="{{ route('admin.deployment.deploy-messages') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-play me-2"></i> Lancer Messages
                </button>
            </form>
        </div>
    </div>

    {{-- Module Communiqués --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-bullhorn text-danger me-2"></i>
                Deploiement Module Communiques
            </h5>

            <p class="text-muted mb-3">
                Cree la table communiques (annonces officielles broadcast)
            </p>

            <form action="{{ route('admin.deployment.deploy-communiques') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-play me-2"></i> Lancer Communiques
                </button>
            </form>
        </div>
    </div>

    {{-- Module Taches --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-tasks me-2" style="color: #6f42c1;"></i>
                Deploiement Module Taches
            </h5>

            <p class="text-muted mb-3">
                Cree les tables taches et tache_commentaires (gestion Directeur → Agent)
            </p>

            <form action="{{ route('admin.deployment.deploy-taches') }}" method="POST">
                @csrf
                <button type="submit" class="btn" style="background-color: #6f42c1; border-color: #6f42c1; color: #fff;">
                    <i class="fas fa-play me-2"></i> Lancer Taches
                </button>
            </form>
        </div>
    </div>

    {{-- Module Plan de Travail --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-calendar-check me-2" style="color: #0d6efd;"></i>
                Deploiement Module Plan de Travail
            </h5>

            <p class="text-muted mb-3">
                Cree la table activite_plans (Plan de Travail Annuel par unite organisationnelle)
            </p>

            <form action="{{ route('admin.deployment.deploy-plan-travail') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-play me-2"></i> Lancer Plan de Travail
                </button>
            </form>
        </div>
    </div>

    {{-- Renommage Roles --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-user-tag me-2" style="color: #20c997;"></i>
                Renommage des Roles
            </h5>

            <p class="text-muted mb-3">
                Renomme les roles : Chef Section RH &rarr; Section ressources humaines, Chef Section NT &rarr; Section Nouvelle Technologie
            </p>

            <form action="{{ route('admin.deployment.deploy-rename-roles') }}" method="POST">
                @csrf
                <button type="submit" class="btn" style="background-color: #20c997; border-color: #20c997; color: #fff;">
                    <i class="fas fa-play me-2"></i> Lancer Renommage
                </button>
            </form>
        </div>
    </div>

    {{-- Domaine d'études --}}
    <div class="col-lg-6 mb-4">
        <div class="form-card">
            <h5 class="mb-3">
                <i class="fas fa-graduation-cap me-2" style="color: #6f42c1;"></i>
                Domaine d'études
            </h5>

            <p class="text-muted mb-3">
                Ajoute la colonne domaine_etudes à la table agents (complément du niveau d'études)
            </p>

            <form action="{{ route('admin.deployment.deploy-domaine-etudes') }}" method="POST">
                @csrf
                <button type="submit" class="btn" style="background-color: #6f42c1; border-color: #6f42c1; color: #fff;">
                    <i class="fas fa-play me-2"></i> Lancer Déploiement
                </button>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="form-card">
            <h6 class="mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Information
            </h6>

            <div class="small text-muted">
                <p><strong>Module Organe:</strong> Crée la table organes et insère les données de base (Secrétariat Exécutif National, Provincial, Local)</p>
                <p><strong>Système Utilisateurs:</strong> Ajoute les relations entre utilisateurs et agents, et entre utilisateurs et rôles</p>
                <p><strong>Module Institutions:</strong> Crée les tables institution_categories et institutions avec les 11 catégories (Institutions politiques, Ministères, etc.) et ~70 institutions individuelles</p>
                <p><strong>Module Messages:</strong> Cree la table messages pour la messagerie interne (DRH vers agents) avec sujet, contenu et statut de lecture</p>
                <p><strong>Module Communiques:</strong> Cree la table communiques pour les annonces officielles broadcast (SEN/SENA vers tous les agents) avec urgence, signataire et expiration</p>
                <p><strong>Module Taches:</strong> Cree les tables taches et tache_commentaires pour la gestion des taches (Directeur assigne des taches aux agents de son departement, suivi de statut avec commentaires)</p>
                <p><strong>Module Plan de Travail:</strong> Cree la table activite_plans pour le Plan de Travail Annuel (activites planifiees par unite organisationnelle SEN/SEP/SEL, avec progression et trimestres)</p>
                <p><strong>Renommage Roles:</strong> Renomme les roles dans la base de donnees (Chef Section RH &rarr; Section ressources humaines, Chef Section NT &rarr; Section Nouvelle Technologie)</p>
                <p><strong>Domaine d'études:</strong> Ajoute la colonne domaine_etudes pour préciser le domaine d'études de chaque agent (ex: Sciences informatiques, Droit, Médecine)</p>
                <p>Ces déploiements sont <strong>idempotents</strong> - ils peuvent être exécutés plusieurs fois sans risque.</p>
            </div>
        </div>
    </div>
</div>

@endsection
