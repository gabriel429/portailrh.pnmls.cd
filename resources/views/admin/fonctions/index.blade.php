@extends('admin.layouts.sidebar')
@section('title', 'Fonctions')
@section('page-title', 'Gestion des Fonctions / Postes')

@section('topbar-actions')
<a href="{{ route('admin.fonctions.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle fonction
</a>
@endsection

@section('content')
<div class="admin-table">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Niveau</th>
                <th>Type</th>
                <th>Description</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($fonctions as $fonction)
            <tr>
                <td class="fw-semibold">{{ $fonction->nom }}</td>
                <td>
                    @php
                        $colors = ['département'=>'primary','section'=>'info','cellule'=>'success','transversal'=>'secondary'];
                        $c = $colors[$fonction->niveau] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $c }}">{{ ucfirst($fonction->niveau) }}</span>
                </td>
                <td>
                    @if($fonction->est_chef)
                        <span class="badge bg-warning text-dark"><i class="fas fa-crown me-1"></i>Responsable</span>
                    @else
                        <span class="badge bg-light text-dark">Collaborateur</span>
                    @endif
                </td>
                <td class="text-muted small">{{ Str::limit($fonction->description, 60) }}</td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.fonctions.edit', $fonction) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.fonctions.destroy', $fonction) }}" method="POST"
                              onsubmit="return confirm('Supprimer cette fonction ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Aucune fonction enregistrée.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($fonctions->hasPages())
    <div class="p-3">{{ $fonctions->links() }}</div>
    @endif
</div>
@endsection
