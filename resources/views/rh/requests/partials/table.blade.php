@if($requests->count() > 0)
    <div class="rh-table-wrap">
        <table class="rh-table">
            <thead>
                <tr>
                    @if($isRH ?? false)<th>Agent</th>@endif
                    <th>Type</th>
                    <th>Période</th>
                    <th>Statut</th>
                    <th>Date création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                    <tr>
                        @if($isRH ?? false)
                        <td>
                            <strong>{{ $request->agent->prenom }} {{ $request->agent->nom }}</strong><br>
                            <small class="text-muted">{{ $request->agent->id_agent }}</small>
                        </td>
                        @endif
                        <td>
                            <span class="rh-pill st-mid">{{ ucfirst($request->type) }}</span>
                        </td>
                        <td>
                            <small>
                                {{ $request->date_debut->format('d/m/Y') }}
                                @if($request->date_fin)
                                    à {{ $request->date_fin->format('d/m/Y') }}
                                @endif
                            </small>
                        </td>
                        <td>
                            @switch($request->statut)
                                @case('en_attente')
                                    <span class="rh-pill st-mid">En attente</span>
                                    @break
                                @case('approuvé')
                                    <span class="rh-pill st-ok">Approuvé</span>
                                    @break
                                @case('rejeté')
                                    <span class="rh-pill st-bad">Rejeté</span>
                                    @break
                                @case('annulé')
                                    <span class="rh-pill st-neutral">Annulé</span>
                                    @break
                                @default
                                    <span class="rh-pill st-neutral">{{ ucfirst($request->statut) }}</span>
                            @endswitch
                        </td>
                        <td><small>{{ $request->created_at->format('d/m/Y H:i') }}</small></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('requests.show', $request) }}" class="btn btn-outline-primary" title="Détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($isRH ?? false)
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
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
        <div class="text-muted small">
            Affichage {{ $requests->firstItem() ?? 0 }} à {{ $requests->lastItem() ?? 0 }} sur {{ $requests->total() }} demandes
        </div>
        {{ $requests->links() }}
    </div>
@else
    <div class="text-center py-5">
        <i class="fas fa-inbox fa-4x text-muted mb-3 d-block"></i>
        <h5 class="text-muted">Aucune demande</h5>
        <p class="text-muted">
            @if($isRH ?? false)
                Il n'y a aucune demande à afficher.
            @else
                Vous n'avez pas encore soumis de demande.
            @endif
        </p>
        <a href="{{ route('requests.create') }}" class="btn btn-primary mt-2">
            <i class="fas fa-plus me-2"></i> Créer une demande
        </a>
    </div>
@endif
