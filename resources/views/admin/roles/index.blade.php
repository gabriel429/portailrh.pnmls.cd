@extends('admin.layouts.sidebar')
@section('title', 'Rôles')
@section('page-title', 'Rôles du système')

@section('topbar-actions')
<a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouveau rôle
</a>
@endsection

@section('content')

@include('admin.partials._index-header', [
    'icon'  => 'fa-user-tag',
    'title' => 'Rôles & Permissions',
    'desc'  => 'Niveaux d\'accès des agents au portail RH',
    'color' => '#f59e0b',
    'bg'    => '#fef3c7',
    'stats' => [
        ['label' => 'Total', 'value' => $roles->total()],
        ['label' => 'Agents', 'value' => $roles->sum('agents_count')],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nom du rôle</th>
                    <th>Description</th>
                    <th>Agents</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr>
                    <td class="fw-semibold">{{ $role->nom_role }}</td>
                    <td class="text-muted" style="font-size:.85rem;">{{ $role->description ?? '–' }}</td>
                    <td>
                        @if($role->agents_count > 0)
                            <span class="badge" style="background:#d1fae5;color:#10b981;">{{ $role->agents_count }}</span>
                        @else
                            <span class="badge" style="background:#f1f5f9;color:#9ca3af;">0</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Supprimer ce rôle ?')">
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
                <tr><td colspan="4">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-user-tag"></i></div>
                        <p>Aucun rôle enregistré</p>
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Ajouter</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($roles->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $roles->firstItem() }}–{{ $roles->lastItem() }} sur {{ $roles->total() }}</span>
        {{ $roles->links() }}
    </div>
    @endif
</div>
@endsection
