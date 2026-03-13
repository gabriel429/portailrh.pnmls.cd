@extends('admin.layouts.sidebar')
@section('title', 'Localités SEL')
@section('page-title', 'Localités – Secrétariats Exécutifs Locaux')

@section('topbar-actions')
<a href="{{ route('admin.localites.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle localité
</a>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
    {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="admin-table">
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
                <td><span class="badge bg-secondary">{{ $localite->code }}</span></td>
                <td class="fw-semibold">{{ $localite->nom }}</td>
                <td>
                    @php $typeLabels = \App\Models\Localite::types(); @endphp
                    <span class="badge bg-info text-dark">
                        {{ $typeLabels[$localite->type] ?? $localite->type }}
                    </span>
                </td>
                <td>{{ $localite->province?->nom ?? '–' }}</td>
                <td><span class="badge bg-light text-dark border">{{ $localite->affectations_count ?? 0 }}</span></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.localites.edit', $localite) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.localites.destroy', $localite) }}" method="POST"
                              onsubmit="return confirm('Supprimer cette localité ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Aucune localité enregistrée.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($localites->hasPages())
    <div class="p-3">{{ $localites->links() }}</div>
    @endif
</div>
@endsection
