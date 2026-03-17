@extends('admin.layouts.sidebar')
@section('title', 'Catégories Documents')
@section('page-title', 'Catégories de documents')

@section('topbar-actions')
<a href="{{ route('admin.categories-documents.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Ajouter
</a>
@endsection

@section('content')
@include('admin.partials._index-header', [
    'icon'  => 'fa-tags',
    'title' => 'Catégories de documents',
    'desc'  => 'Catégories utilisées pour classer les documents de travail',
    'color' => '#d97706',
    'bg'    => '#fef3c7',
    'stats' => [
        ['label' => 'Total',   'value' => $categories->count()],
        ['label' => 'Actives', 'value' => $categories->where('actif', true)->count()],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Icône</th>
                    <th>Nom</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td>
                        @if($cat->icone)
                            <span class="badge bg-light text-dark border" style="font-size:.85rem;">
                                <i class="fas {{ $cat->icone }}"></i>
                            </span>
                        @else
                            <span class="text-muted">–</span>
                        @endif
                    </td>
                    <td><strong>{{ $cat->nom }}</strong></td>
                    <td>
                        @if($cat->actif)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.categories-documents.edit', $cat) }}" class="btn btn-outline-primary" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.categories-documents.destroy', $cat) }}" style="display:inline;" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" title="Supprimer"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-tags"></i></div>
                            <p>Aucune catégorie</p>
                            <a href="{{ route('admin.categories-documents.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Ajouter une catégorie
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
