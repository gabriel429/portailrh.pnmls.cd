@extends('admin.layouts.sidebar')
@section('title', 'Localités')
@section('page-title', 'Localités – Secrétariats Exécutifs Locaux')

@section('topbar-actions')
<a href="{{ route('admin.localites.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle localité
</a>
@endsection

@section('content')

@include('admin.partials._index-header', [
    'icon'  => 'fa-map-pin',
    'title' => 'Localités SEL',
    'desc'  => 'Territoires, zones de santé et communes des Secrétariats Exécutifs Locaux',
    'color' => '#0d9488',
    'bg'    => '#ccfbf1',
    'stats' => [
        ['label' => 'Total', 'value' => $localites->total()],
        ['label' => 'Affectations', 'value' => $localites->sum('affectations_count')],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Province</th>
                    <th>Affectations</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($localites as $localite)
                <tr>
                    <td><span class="badge badge-sel">{{ $localite->code }}</span></td>
                    <td class="fw-semibold">{{ $localite->nom }}</td>
                    <td>
                        @php $typeLabels = \App\Models\Localite::types(); @endphp
                        <span class="badge" style="background:#ccfbf1;color:#0d9488;">{{ $typeLabels[$localite->type] ?? $localite->type }}</span>
                    </td>
                    <td>{{ $localite->province?->nom ?? '–' }}</td>
                    <td><span class="badge" style="background:#f0f5ff;color:#4f46e5;">{{ $localite->affectations_count ?? 0 }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.localites.edit', $localite) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.localites.destroy', $localite) }}" method="POST" onsubmit="return confirm('Supprimer cette localité ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-map-pin"></i></div>
                        <p>Aucune localité enregistrée</p>
                        <a href="{{ route('admin.localites.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Ajouter</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($localites->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $localites->firstItem() }}–{{ $localites->lastItem() }} sur {{ $localites->total() }}</span>
        {{ $localites->links() }}
    </div>
    @endif
</div>
@endsection
