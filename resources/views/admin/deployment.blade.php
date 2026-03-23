@extends('admin.layouts.sidebar')

@section('title', 'Déploiement')
@section('page-title', 'Assistant de Déploiement')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Succès!</strong> Le déploiement est terminé.
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

<div class="row">
    {{-- Git Pull - Deployer les modifications --}}
    <div class="col-lg-12 mb-4">
        <div class="form-card" style="border-left: 4px solid #0d6efd;">
            <h5 class="mb-3">
                <i class="fab fa-github me-2" style="color: #0d6efd;"></i>
                Deployer les modifications (Git Pull)
            </h5>
            <p class="text-muted mb-3">
                <strong>Action principale :</strong> Tire les dernières modifications depuis GitHub (<code>git pull origin main</code>), nettoie les caches et applique les migrations automatiquement.
            </p>
            <form action="{{ route('admin.deployment.git-pull') }}" method="POST" onsubmit="return confirm('Deployer les dernieres modifications depuis GitHub ?');">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-cloud-download-alt me-2"></i> Deployer les modifications
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
