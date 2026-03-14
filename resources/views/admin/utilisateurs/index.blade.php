@extends('admin.layouts.sidebar')

@section('title', 'Gestion des Utilisateurs')
@section('page-title', 'Utilisateurs du Système')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2 text-primary"></i>
                    Liste des Utilisateurs
                </h5>
                <a href="{{ route('admin.utilisateurs.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus me-2"></i> Nouvel Utilisateur
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ $user->id }}</span>
                                </td>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    @if ($user->id === auth()->id())
                                        <span class="badge bg-info ms-2">Votre compte</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.utilisateurs.edit', $user) }}"
                                       class="btn btn-sm btn-info" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if ($user->id !== auth()->id())
                                        <form action="{{ route('admin.utilisateurs.destroy', $user) }}"
                                              method="POST" style="display:inline;"
                                              onsubmit="return confirm('Êtes-vous sûr?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox me-2"></i>
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <nav aria-label="Page navigation" class="mt-3">
                    {{ $users->links() }}
                </nav>
            @endif
        </div>
    </div>
</div>
@endsection
