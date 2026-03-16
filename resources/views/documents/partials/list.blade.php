@if($docs->count() > 0)
    <div class="row g-3">
        @foreach($docs as $document)
            <div class="col-md-6 col-lg-4">
                <div class="doc-card">
                    <div class="doc-card-top">
                        {{-- Icône type fichier --}}
                        <div class="doc-icon-wrap {{ $document->type_fichier === 'pdf' ? 'pdf' : (in_array($document->type_fichier, ['jpg','jpeg','png','gif','webp']) ? 'image' : (in_array($document->type_fichier, ['doc','docx']) ? 'word' : (in_array($document->type_fichier, ['xls','xlsx']) ? 'excel' : 'other'))) }}">
                            @if($document->type_fichier === 'pdf')
                                <i class="fas fa-file-pdf"></i>
                            @elseif(in_array($document->type_fichier, ['jpg','jpeg','png','gif','webp']))
                                <i class="fas fa-file-image"></i>
                            @elseif(in_array($document->type_fichier, ['doc','docx']))
                                <i class="fas fa-file-word"></i>
                            @elseif(in_array($document->type_fichier, ['xls','xlsx']))
                                <i class="fas fa-file-excel"></i>
                            @else
                                <i class="fas fa-file"></i>
                            @endif
                        </div>

                        <div class="doc-card-info">
                            <h6>{{ Str::limit($document->nom_fichier, 35) }}</h6>

                            @if($document->description)
                                <div class="doc-card-desc">{{ Str::limit($document->description, 70) }}</div>
                            @endif

                            {{-- Badge catégorie --}}
                            <div class="mb-2">
                                @switch($document->categorie)
                                    @case('identite')
                                        <span class="doc-badge identite"><i class="fas fa-id-card"></i> Identité</span>
                                        @break
                                    @case('parcours')
                                        <span class="doc-badge parcours"><i class="fas fa-graduation-cap"></i> Parcours</span>
                                        @break
                                    @case('carriere')
                                        <span class="doc-badge carriere"><i class="fas fa-briefcase"></i> Carrière</span>
                                        @break
                                    @case('mission')
                                        <span class="doc-badge mission"><i class="fas fa-plane"></i> Mission</span>
                                        @break
                                @endswitch
                            </div>

                            {{-- Méta infos --}}
                            <div class="doc-meta">
                                <span><i class="fas fa-hdd"></i> {{ number_format($document->taille / 1024, 1) }} KB</span>
                                <span><i class="fas fa-calendar-alt"></i> {{ $document->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="doc-card-actions">
                        <a href="{{ route('documents.show', $document) }}" class="doc-action view" title="Voir les détails">
                            <i class="fas fa-eye"></i> Détails
                        </a>
                        <a href="{{ route('documents.download', $document) }}" class="doc-action download" title="Télécharger">
                            <i class="fas fa-download"></i> Télécharger
                        </a>
                        @if(auth()->user()->id === $document->agent_id || auth()->user()->hasAdminAccess())
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" style="flex:1;display:flex;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="doc-action delete" style="width:100%;" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i> Suppr.
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-folder-open"></i>
        </div>
        <h5>Aucun document trouvé</h5>
        <p>Vous n'avez pas encore uploadé de documents. Commencez par en ajouter un !</p>
        <a href="{{ route('documents.create') }}" class="btn btn-filter">
            <i class="fas fa-cloud-upload-alt me-2"></i>Uploader un document
        </a>
    </div>
@endif
