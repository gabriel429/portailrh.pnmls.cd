@extends('admin.layouts.sidebar')
@section('title', 'Cellules')
@section('page-title', 'Gestion des Cellules')

@section('topbar-actions')
<a href="{{ route('admin.cellules.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle cellule
</a>
@endsection

@section('content')
<div class="admin-table">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Section</th>
                <th>Département</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($cellules as $cellule)
            <tr>
                <td><span class="badge bg-secondary">{{ $cellule->code }}</span></td>
                <td class="fw-semibold">{{ $cellule->nom }}</td>
                <td>{{ $cellule->section?->nom ?? '–' }}</td>
                <td>{{ $cellule->section?->department?->nom ?? '–' }}</td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.cellules.edit', $cellule) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.cellules.destroy', $cellule) }}" method="POST"
                              onsubmit="return confirm('Supprimer cette cellule ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Aucune cellule enregistrée.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($cellules->hasPages())
    <div class="p-3">{{ $cellules->links() }}</div>
    @endif
</div>
@endsection
