@extends('admin.layouts.sidebar')
@section('title', 'Départements')
@section('page-title', 'Gestion des Départements')

@section('topbar-actions')
<a href="{{ route('admin.departments.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouveau département
</a>
@endsection

@section('content')
<div class="admin-table">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Province</th>
                <th>Agents</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($departments as $dept)
            <tr>
                <td><span class="badge bg-secondary">{{ $dept->code }}</span></td>
                <td class="fw-semibold">{{ $dept->nom }}</td>
                <td>{{ $dept->province?->nom ?? '–' }}</td>
                <td><span class="badge bg-primary">{{ $dept->agents_count }}</span></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.departments.edit', $dept) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.departments.destroy', $dept) }}" method="POST"
                              onsubmit="return confirm('Supprimer ce département ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Aucun département enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($departments->hasPages())
    <div class="p-3">{{ $departments->links() }}</div>
    @endif
</div>
@endsection
