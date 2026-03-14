@extends('admin.layouts.sidebar')

@section('title', 'Organes')
@section('page-title', 'Gestion des Organes')

@section('topbar-actions')
<a href="{{ route('admin.organes.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-2"></i> Ajouter un Organe
</a>
@endsection

@section('content')

<div class="panel">
    <table class="admin-table w-100">
        <thead>
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Sigle</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($organes as $o)
            <tr>
                <td><strong>{{ $o->code }}</strong></td>
                <td>{{ $o->nom }}</td>
                <td><span class="badge bg-secondary">{{ $o->sigle ?? '–' }}</span></td>
                <td>
                    @if($o->actif)
                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Actif</span>
                    @else
                        <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Inactif</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.organes.edit', $o) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('admin.organes.destroy', $o) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Êtes-vous sûr ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    Aucun organe enregistré.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $organes->links() }}

@endsection
