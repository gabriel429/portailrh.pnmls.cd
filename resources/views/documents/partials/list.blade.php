@if($docs->count() > 0)
    <div class="row g-4">
        @foreach($docs as $document)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm document-card h-100">
                    <div class="card-body">
                        <!-- Icône et type -->
                        <div class="mb-3">
                            @if($document->type_fichier === 'pdf')
                                <i class="fas fa-file-pdf fa-3x text-danger"></i>
                            @elseif(in_array($document->type_fichier, ['jpg', 'jpeg', 'png']))
                                <i class="fas fa-file-image fa-3x text-info"></i>
                            @else
                                <i class="fas fa-file fa-3x text-muted"></i>
                            @endif
                        </div>

                        <!-- Nom et catégorie -->
                        <h6 class="card-title">
                            {{ Str::limit($document->nom_fichier, 30) }}
                        </h6>
                        <p class="card-text text-muted small">
                            <span class="badge bg-light text-dark">
                                @switch($document->categorie)
                                    @case('identite')
                                        <i class="fas fa-id-card me-1"></i> Identité
                                        @break
                                    @case('parcours')
                                        <i class="fas fa-graduation-cap me-1"></i> Parcours
                                        @break
                                    @case('carriere')
                                        <i class="fas fa-briefcase me-1"></i> Carrière
                                        @break
                                    @case('mission')
                                        <i class="fas fa-plane me-1"></i> Mission
                                        @break
                                @endswitch
                            </span>
                        </p>

                        <!-- Description -->
                        @if($document->description)
                            <p class="small text-muted mb-2">
                                {{ Str::limit($document->description, 80) }}
                            </p>
                        @endif

                        <!-- Infos fichier -->
                        <div class="small text-muted mb-3">
                            <p class="mb-1">
                                <i class="fas fa-hdd me-1"></i>
                                {{ number_format($document->taille / 1024, 2) }} KB
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $document->created_at->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-footer bg-light border-top-0">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <a href="{{ route('documents.show', $document) }}" class="btn btn-outline-primary" title="Détails">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('documents.download', $document) }}" class="btn btn-outline-success" title="Télécharger">
                                <i class="fas fa-download"></i>
                            </a>
                            @if(auth()->user()->id === $document->agent_id || auth()->user()->hasAdminAccess())
                                <form method="POST" action="{{ route('documents.destroy', $document) }}" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-5">
        <i class="fas fa-inbox fa-5x text-muted mb-3 d-block"></i>
        <h5 class="text-muted">Aucun document</h5>
        <p class="text-muted mb-3">Vous n'avez pas encore uploadé de documents</p>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="fas fa-cloud-upload-alt me-2"></i> Uploader un document
        </a>
    </div>
@endif
