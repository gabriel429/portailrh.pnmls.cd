@extends('layouts.app')

@section('title', 'Affectations - Portail RH PNMLS')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-user-tie me-2 text-primary"></i>Gestion des Affectations</h3>
        <a href="{{ route('rh.affectations.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nouvelle affectation
        </a>
    </div>

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

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Agent</th>
                            <th>Fonction</th>
                            <th>Niv. admin.</th>
                            <th>Niveau</th>
                            <th>Entité rattachée</th>
                            <th>Début</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($affectations as $aff)
                        <tr>
                            <td class="fw-semibold">{{ $aff->agent?->nom }} {{ $aff->agent?->postnom }}</td>
                            <td>{{ $aff->fonction?->nom ?? '–' }}</td>
                            <td>
                                @php $naColor = match($aff->niveau_administratif ?? '') { 'SEN' => 'primary', 'SEP' => 'success', 'SEL' => 'info', default => 'secondary' }; @endphp
                                <span class="badge bg-{{ $naColor }}">{{ $aff->niveau_administratif ?? '–' }}</span>
                            </td>
                            <td>
                                @php $niveauClass = match($aff->niveau ?? '') { 'direction' => 'dark', 'service_rattache' => 'warning', 'département' => 'primary', 'section' => 'info', 'cellule' => 'secondary', 'province' => 'success', 'local' => 'teal', default => 'light' }; @endphp
                                <span class="badge bg-{{ $niveauClass }} text-{{ in_array($aff->niveau, ['warning','light']) ? 'dark' : 'white' }}">{{ $aff->niveau ?? '–' }}</span>
                            </td>
                            <td>
                                @if(in_array($aff->niveau_administratif, ['SEP']))
                                    {{ $aff->province?->nom ?? '–' }}
                                @elseif($aff->niveau_administratif === 'SEL')
                                    {{ $aff->localite?->nom ?? '–' }}
                                @elseif($aff->niveau === 'cellule')
                                    {{ $aff->cellule?->nom ?? '–' }}
                                @elseif(in_array($aff->niveau, ['section','service_rattache']))
                                    {{ $aff->section?->nom ?? '–' }}
                                @elseif($aff->niveau === 'département')
                                    {{ $aff->department?->nom ?? '–' }}
                                @else
                                    –
                                @endif
                            </td>
                            <td>{{ $aff->date_debut ? \Carbon\Carbon::parse($aff->date_debut)->format('d/m/Y') : '–' }}</td>
                            <td>
                                @if($aff->actif)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-danger">Inactif</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('rh.affectations.edit', $aff) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('rh.affectations.destroy', $aff) }}" method="POST"
                                          onsubmit="return confirm('Supprimer cette affectation ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucune affectation enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($affectations->hasPages())
            <div class="p-3">{{ $affectations->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
