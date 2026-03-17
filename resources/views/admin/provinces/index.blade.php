@extends('admin.layouts.sidebar')
@section('title', 'Provinces')
@section('page-title', 'Gestion des Provinces')

@section('topbar-actions')
<a href="{{ route('admin.provinces.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle province
</a>
@endsection

@section('content')

@include('admin.partials._index-header', [
    'icon'  => 'fa-map-marked-alt',
    'title' => 'Provinces',
    'desc'  => 'Secrétariats Exécutifs Provinciaux (SEP)',
    'color' => '#10b981',
    'bg'    => '#d1fae5',
    'stats' => [
        ['label' => 'Total', 'value' => $provinces->total()],
        ['label' => 'Agents', 'value' => $provinces->sum('agents_count')],
        ['label' => 'Depts', 'value' => $provinces->sum('departments_count')],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Ville secrétariat</th>
                    <th>Secrétaire exécutif</th>
                    <th>Agents</th>
                    <th>Depts</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($provinces as $province)
                <tr>
                    <td><span class="badge badge-sep">{{ $province->code }}</span></td>
                    <td class="fw-semibold">{{ $province->nom }}</td>
                    <td>{{ $province->ville_secretariat ?? '–' }}</td>
                    <td>{{ $province->nom_secretariat_executif ?? '–' }}</td>
                    <td><span class="badge" style="background:#dbeafe;color:#2563eb;">{{ $province->agents_count }}</span></td>
                    <td><span class="badge" style="background:#ede9fe;color:#6366f1;">{{ $province->departments_count }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.provinces.edit', $province) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.provinces.destroy', $province) }}" method="POST" onsubmit="return confirm('Supprimer cette province ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-map-marked-alt"></i></div>
                        <p>Aucune province enregistrée</p>
                        <a href="{{ route('admin.provinces.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Ajouter</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($provinces->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $provinces->firstItem() }}–{{ $provinces->lastItem() }} sur {{ $provinces->total() }}</span>
        {{ $provinces->links() }}
    </div>
    @endif
</div>
@endsection
