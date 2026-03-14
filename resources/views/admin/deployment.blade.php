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
                <p>Ces déploiements sont <strong>idempotents</strong> - ils peuvent être exécutés plusieurs fois sans risque.</p>
            </div>
        </div>
    </div>
</div>

@endsection
