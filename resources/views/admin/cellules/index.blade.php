@extends('admin.layouts.sidebar')
@section('title', 'Cellules')
@section('page-title', 'Gestion des Cellules')

@section('topbar-actions')
<a href="{{ route('admin.cellules.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle cellule
</a>
@endsection

@section('content')

@include('admin.partials._index-header', [
    'icon'  => 'fa-cubes',
    'title' => 'Cellules',
    'desc'  => 'Unités au sein des sections départementales',
    'color' => '#64748b',
    'bg'    => '#f1f5f9',
    'stats' => [
        ['label' => 'Total', 'value' => $cellules->total()],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Rattachement</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cellules as $cellule)
                <tr>
                    <td><span class="badge" style="background:#f1f5f9;color:#64748b;">{{ $cellule->code }}</span></td>
                    <td class="fw-semibold">{{ $cellule->nom }}</td>
                    <td>
                        <span style="font-size:.78rem;color:#9ca3af;">
                            @if($cellule->section?->department)
                                <i class="fas fa-building me-1"></i>{{ $cellule->section->department->nom }}
                                <i class="fas fa-chevron-right mx-1" style="font-size:.55rem;"></i>
                            @endif
                            @if($cellule->section)
                                <i class="fas fa-sitemap me-1"></i>{{ $cellule->section->nom }}
                            @else
                                –
                            @endif
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.cellules.edit', $cellule) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.cellules.destroy', $cellule) }}" method="POST" onsubmit="return confirm('Supprimer cette cellule ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-cubes"></i></div>
                        <p>Aucune cellule enregistrée</p>
                        <a href="{{ route('admin.cellules.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Ajouter</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cellules->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $cellules->firstItem() }}–{{ $cellules->lastItem() }} sur {{ $cellules->total() }}</span>
        {{ $cellules->links() }}
    </div>
    @endif
</div>
@endsection
