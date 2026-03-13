@extends('admin.layouts.sidebar')
@section('title', 'Affectations')
@section('page-title', 'Gestion des Affectations')

@section('topbar-actions')
<a href="{{ route('admin.affectations.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle affectation
</a>
@endsection

@section('content')
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
    {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="admin-table">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Agent</th>
                <th>Fonction</th>
                <th>Niveau</th>
                <th>Entité rattachée</th>
                <th>Début</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($affectations as $aff)
            <tr>
                <td class="fw-semibold">{{ $aff->agent?->nom }} {{ $aff->agent?->postnom }}</td>
                <td>{{ $aff->fonction?->nom ?? '–' }}</td>
                <td>
                    @php $niveauClass = match($aff->niveau) { 'département' => 'primary', 'section' => 'info', 'cellule' => 'secondary', default => 'light' }; @endphp
                    <span class="badge bg-{{ $niveauClass }}">{{ ucfirst($aff->niveau) }}</span>
                </td>
                <td>
                    @if($aff->niveau === 'cellule')
                        {{ $aff->cellule?->nom ?? '–' }}
                    @elseif($aff->niveau === 'section')
                        {{ $aff->section?->nom ?? '–' }}
                    @else
                        {{ $aff->department?->nom ?? '–' }}
                    @endif
                </td>
                <td>{{ $aff->date_debut ? \Carbon\Carbon::parse($aff->date_debut)->format('d/m/Y') : '–' }}</td>
                <td>
                    @if($aff->actif)
                        <span class="badge bg-success">Actif</span>
                    @else
                        <span class="badge bg-danger">Inactif</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.affectations.edit', $aff) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.affectations.destroy', $aff) }}" method="POST"
                              onsubmit="return confirm('Supprimer cette affectation ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Aucune affectation enregistrée.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($affectations->hasPages())
    <div class="p-3">{{ $affectations->links() }}</div>
    @endif
</div>
@endsection
