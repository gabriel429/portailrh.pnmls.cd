@extends('admin.layouts.sidebar')

@section('title', 'Gestion des Utilisateurs')
@section('page-title', 'Utilisateurs du Système')

@section('topbar-actions')
<a href="{{ route('admin.utilisateurs.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-user-plus me-1"></i> Nouvel utilisateur
</a>
@endsection

@section('content')

@include('admin.partials._index-header', [
    'icon'  => 'fa-users-cog',
    'title' => 'Utilisateurs',
    'desc'  => 'Comptes d\'accès au portail RH PNMLS',
    'color' => '#ec4899',
    'bg'    => '#fce7f3',
    'stats' => [
        ['label' => 'Total', 'value' => $users->total()],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Agent</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Créé le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td><span class="badge" style="background:#fce7f3;color:#ec4899;">{{ $user->id }}</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#ec4899,#be185d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.65rem;font-weight:700;flex-shrink:0;">
                                {{ strtoupper(substr($user->agent?->prenom ?? 'U', 0, 1)) }}{{ strtoupper(substr($user->agent?->nom ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <span class="fw-semibold">{{ $user->agent?->prenom }} {{ $user->agent?->nom ?? $user->name }}</span>
                                @if($user->id === auth()->id())
                                    <span class="badge" style="background:#dbeafe;color:#2563eb;font-size:.65rem;margin-left:4px;">Vous</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.85rem;">{{ $user->email }}</td>
                    <td>
                        @if($user->role)
                            <span class="badge" style="background:#d1fae5;color:#10b981;">{{ $user->role->nom_role }}</span>
                        @else
                            <span class="text-muted">–</span>
                        @endif
                    </td>
                    <td style="font-size:.82rem;color:#6b7280;">{{ $user->created_at?->format('d/m/Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.utilisateurs.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.utilisateurs.destroy', $user) }}" method="POST" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                            @else
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Votre propre compte"><i class="fas fa-trash"></i></button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-users-cog"></i></div>
                        <p>Aucun utilisateur trouvé</p>
                        <a href="{{ route('admin.utilisateurs.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-plus me-1"></i> Ajouter</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $users->firstItem() }}–{{ $users->lastItem() }} sur {{ $users->total() }}</span>
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
