@extends('admin.layouts.sidebar')
@section('title', 'Sections')
@section('page-title', 'Gestion des Sections')

@section('topbar-actions')
<a href="{{ route('admin.sections.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle section
</a>
@endsection

@section('content')
<div class="admin-table">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Département</th>
                <th>Cellules</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($sections as $section)
            <tr>
                <td><span class="badge bg-secondary">{{ $section->code }}</span></td>
                <td class="fw-semibold">{{ $section->nom }}</td>
                <td>
                    @if(($section->type ?? 'section') === 'service_rattache')
                        <span class="badge bg-warning text-dark"><i class="fas fa-project-diagram me-1"></i>Service rattaché</span>
                    @else
                        <span class="badge bg-primary"><i class="fas fa-layer-group me-1"></i>Section</span>
                    @endif
                </td>
                <td>{{ $section->department?->nom ?? '<span class="text-muted fst-italic">SEN/SENA</span>' }}</td>
                <td><span class="badge bg-info text-dark">{{ $section->cellules_count }}</span></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.sections.edit', $section) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.sections.destroy', $section) }}" method="POST"
                              onsubmit="return confirm('Supprimer cette section et ses cellules ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Aucune section enregistrée.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($sections->hasPages())
    <div class="p-3">{{ $sections->links() }}</div>
    @endif
</div>
@endsection
