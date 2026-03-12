@extends('admin.layouts.sidebar')
@section('title', 'Rôles')
@section('page-title', 'Rôles du système')

@section('topbar-actions')
<a href="{{ route('admin.roles.create') }}" class="btn btn-warning btn-sm">
    <i class="fas fa-plus me-1"></i> Nouveau rôle
</a>
@endsection

@section('content')
<div class="admin-table">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nom du rôle</th>
                <th>Description</th>
                <th>Agents affectés</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($roles as $role)
            <tr>
                <td class="fw-semibold">{{ $role->nom_role }}</td>
                <td class="text-muted">{{ $role->description ?? '–' }}</td>
                <td>
                    <span class="badge {{ $role->agents_count > 0 ? 'bg-success' : 'bg-secondary' }}">
                        {{ $role->agents_count }}
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST"
                              onsubmit="return confirm('Supprimer ce rôle ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"
                                    {{ $role->agents_count > 0 ? 'disabled' : '' }}
                                    title="{{ $role->agents_count > 0 ? 'Des agents utilisent ce rôle' : 'Supprimer' }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Aucun rôle enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($roles->hasPages())
    <div class="p-3">{{ $roles->links() }}</div>
    @endif
</div>
@endsection
