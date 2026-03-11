@extends('layouts.app')

@section('title', 'Document - Portail RH PNMLS')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h3 class="mb-0">Détails du document</h3>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Type</label>
                            <p class="mb-0">
                                @switch($document->type)
                                    @case('identite')
                                        <span class="badge bg-danger"><i class="fas fa-id-card me-1"></i> Identité</span>
                                        @break
                                    @case('parcours')
                                        <span class="badge bg-info"><i class="fas fa-graduation-cap me-1"></i> Parcours</span>
                                        @break
                                    @case('carriere')
                                        <span class="badge bg-warning"><i class="fas fa-briefcase me-1"></i> Carrière</span>
                                        @break
                                    @case('mission')
                                        <span class="badge bg-success"><i class="fas fa-plane me-1"></i> Mission</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $document->type }}</span>
                                @endswitch
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Statut</label>
                            <p class="mb-0">
                                @if($document->statut === 'valide')
                                    <span class="badge bg-success">Valide</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($document->statut) }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Agent</label>
                            <p class="mb-0">{{ $document->agent->prenom ?? '' }} {{ $document->agent->nom ?? '' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Date d'ajout</label>
                            <p class="mb-0">{{ $document->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted small">Description</label>
                            <p class="mb-0">{{ $document->description ?? 'Aucune description' }}</p>
                        </div>
                    </div>

                    <!-- Aperçu du fichier -->
                    @if($document->fichier)
                        <div class="border rounded p-3 bg-light text-center mb-4">
                            @php
                                $extension = pathinfo($document->fichier, PATHINFO_EXTENSION);
                            @endphp

                            @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
                                <img src="{{ asset($document->fichier) }}" alt="Document" class="img-fluid" style="max-height: 400px;">
                            @elseif(strtolower($extension) === 'pdf')
                                <i class="fas fa-file-pdf fa-5x text-danger mb-3 d-block"></i>
                                <p>Fichier PDF</p>
                            @else
                                <i class="fas fa-file fa-5x text-muted mb-3 d-block"></i>
                                <p>{{ strtoupper($extension) }}</p>
                            @endif
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <form action="{{ route('documents.download', $document) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-download me-2"></i> Télécharger
                            </button>
                        </form>

                        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i> Retour à la liste
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
