@extends('admin.layouts.sidebar')
@section('title', 'Documents de travail')
@section('page-title', 'Documents de travail')

@section('topbar-actions')
<a href="{{ route('admin.documents-travail.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Ajouter
</a>
@endsection

@section('content')
@include('admin.partials._index-header', [
    'icon'  => 'fa-file-invoice',
    'title' => 'Documents de travail',
    'desc'  => 'Documents officiels partagés avec tous les agents',
    'color' => '#ea580c',
    'bg'    => '#ffedd5',
    'stats' => [
        ['label' => 'Total',  'value' => $documents->total()],
        ['label' => 'Actifs', 'value' => $documents->where('actif', true)->count()],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Catégorie</th>
                    <th>Fichier</th>
                    <th>Ajouté par</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                <tr>
                    <td>
                        <strong>{{ $doc->titre }}</strong>
                        @if($doc->description)
                            <br><small class="text-muted">{{ Str::limit($doc->description, 60) }}</small>
                        @endif
                    </td>
                    <td><span class="badge bg-warning text-dark">{{ $doc->categorie }}</span></td>
                    <td>
                        <small>
                            <i class="fas fa-file-{{ match($doc->type_fichier) {
                                'pdf' => 'pdf text-danger',
                                'doc','docx' => 'word text-primary',
                                'xls','xlsx' => 'excel text-success',
                                'ppt','pptx' => 'powerpoint text-warning',
                                'jpg','jpeg','png' => 'image text-info',
                                default => 'alt text-secondary',
                            } }} me-1"></i>
                            .{{ $doc->type_fichier }}
                            @if($doc->taille)
                                · {{ number_format($doc->taille / 1024 / 1024, 1) }} Mo
                            @endif
                        </small>
                    </td>
                    <td><small>{{ $doc->uploader?->agent?->prenom ?? $doc->uploader?->name ?? '–' }}</small></td>
                    <td>
                        @if($doc->actif)
                            <span class="badge bg-success">Actif</span>
                        @else
                            <span class="badge bg-secondary">Masqué</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('documents-travail.download', $doc) }}" class="btn btn-outline-info" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.documents-travail.edit', $doc) }}" class="btn btn-outline-primary" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.documents-travail.destroy', $doc) }}" style="display:inline;" onsubmit="return confirm('Supprimer ce document ?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" title="Supprimer"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-file-invoice"></i></div>
                            <p>Aucun document de travail</p>
                            <a href="{{ route('admin.documents-travail.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Ajouter un document
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($documents->hasPages())
    <div class="pagination-wrapper">
        {{ $documents->links() }}
    </div>
    @endif
</div>
@endsection
