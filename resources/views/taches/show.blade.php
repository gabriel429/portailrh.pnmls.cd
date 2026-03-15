@extends('layouts.app')

@section('title', 'Tache - ' . $tache->titre)

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>{{ $tache->titre }}</h1>
                    <p class="rh-sub">
                        Assignee a {{ $tache->agent?->nom_complet }} par {{ $tache->createur?->nom_complet }}
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('taches.index') }}" class="btn-rh alt">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row mt-3 g-3">
            {{-- Colonne gauche: Detail + Changement statut --}}
            <div class="col-lg-7">
                {{-- Detail de la tache --}}
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-info-circle me-2 text-primary"></i>Details</h3>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            @if($tache->priorite === 'urgente')
                                <span class="badge bg-danger">Urgente</span>
                            @elseif($tache->priorite === 'haute')
                                <span class="badge bg-warning text-dark">Haute</span>
                            @else
                                <span class="badge bg-secondary">Normale</span>
                            @endif
                            @if($tache->statut === 'terminee')
                                <span class="badge bg-success">Terminee</span>
                            @elseif($tache->statut === 'en_cours')
                                <span class="badge bg-primary">En cours</span>
                            @else
                                <span class="badge bg-secondary">Nouvelle</span>
                            @endif
                        </div>
                    </header>
                    <div class="p-3">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Creee par</dt>
                            <dd class="col-sm-8">{{ $tache->createur?->nom_complet }}</dd>

                            <dt class="col-sm-4 text-muted">Assignee a</dt>
                            <dd class="col-sm-8">{{ $tache->agent?->nom_complet }}</dd>

                            <dt class="col-sm-4 text-muted">Date de creation</dt>
                            <dd class="col-sm-8">{{ $tache->created_at->format('d/m/Y a H:i') }}</dd>

                            @if($tache->date_echeance)
                            <dt class="col-sm-4 text-muted">Echeance</dt>
                            <dd class="col-sm-8">
                                {{ $tache->date_echeance->format('d/m/Y') }}
                                @if($tache->date_echeance->isPast() && $tache->statut !== 'terminee')
                                    <span class="badge bg-danger ms-1">En retard</span>
                                @endif
                            </dd>
                            @endif
                        </dl>

                        @if($tache->description)
                            <hr>
                            <div style="white-space: pre-wrap;">{{ $tache->description }}</div>
                        @endif
                    </div>
                </div>

                {{-- Formulaire changement de statut (agent assigne uniquement) --}}
                @if($isAssigne && $tache->statut !== 'terminee')
                <div class="dash-panel mt-3">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-exchange-alt me-2 text-warning"></i>Mettre a jour le statut</h3>
                        </div>
                    </header>
                    <div class="p-3">
                        <form action="{{ route('taches.update-statut', $tache) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="statut" class="form-label fw-bold">Nouveau statut <span class="text-danger">*</span></label>
                                    <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                                        @if($tache->statut === 'nouvelle')
                                            <option value="en_cours" selected>En cours</option>
                                            <option value="terminee">Terminee</option>
                                        @else
                                            <option value="en_cours" {{ $tache->statut === 'en_cours' ? 'selected' : '' }}>En cours</option>
                                            <option value="terminee">Terminee</option>
                                        @endif
                                    </select>
                                    @error('statut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="contenu_statut" class="form-label fw-bold">Commentaire <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('contenu') is-invalid @enderror" id="contenu_statut" name="contenu" rows="3" required placeholder="Decrivez l'avancement ou le resultat...">{{ old('contenu') }}</textarea>
                                    @error('contenu')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-sync-alt me-1"></i> Mettre a jour
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Formulaire commentaire libre --}}
                <div class="dash-panel mt-3">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-comment me-2 text-info"></i>Ajouter un commentaire</h3>
                        </div>
                    </header>
                    <div class="p-3">
                        <form action="{{ route('taches.commentaire', $tache) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <textarea class="form-control" name="contenu" rows="2" required placeholder="Votre commentaire..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-paper-plane me-1"></i> Envoyer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Colonne droite: Timeline commentaires --}}
            <div class="col-lg-5">
                <div class="dash-panel">
                    <header class="panel-head">
                        <div>
                            <h3 class="panel-title"><i class="fas fa-history me-2 text-success"></i>Historique</h3>
                            <p class="panel-sub">{{ $tache->commentaires->count() }} commentaire{{ $tache->commentaires->count() > 1 ? 's' : '' }}</p>
                        </div>
                    </header>
                    <div class="p-3">
                        @forelse($tache->commentaires->sortByDesc('created_at') as $comm)
                            <div class="border-start border-3 {{ $comm->nouveau_statut ? 'border-warning' : 'border-info' }} rounded p-3 mb-2">
                                @if($comm->ancien_statut && $comm->nouveau_statut)
                                    <div class="mb-2">
                                        @php
                                            $statutLabels = [
                                                'nouvelle' => 'Nouvelle',
                                                'en_cours' => 'En cours',
                                                'terminee' => 'Terminee',
                                            ];
                                            $badgeOld = match($comm->ancien_statut) {
                                                'terminee' => 'bg-success',
                                                'en_cours' => 'bg-primary',
                                                default => 'bg-secondary',
                                            };
                                            $badgeNew = match($comm->nouveau_statut) {
                                                'terminee' => 'bg-success',
                                                'en_cours' => 'bg-primary',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeOld }}">{{ $statutLabels[$comm->ancien_statut] ?? $comm->ancien_statut }}</span>
                                        <i class="fas fa-arrow-right mx-1 text-muted" style="font-size: 0.7rem;"></i>
                                        <span class="badge {{ $badgeNew }}">{{ $statutLabels[$comm->nouveau_statut] ?? $comm->nouveau_statut }}</span>
                                    </div>
                                @endif
                                <p class="mb-1">{{ $comm->contenu }}</p>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>{{ $comm->agent?->nom_complet }}
                                    &bull; {{ $comm->created_at->format('d/m/Y a H:i') }}
                                </small>
                            </div>
                        @empty
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-comments fa-2x mb-2 d-block"></i>
                                <p class="mb-0">Aucun commentaire.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
