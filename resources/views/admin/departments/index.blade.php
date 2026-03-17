@extends('admin.layouts.sidebar')
@section('title', 'Départements')
@section('page-title', 'Gestion des Départements')

@section('topbar-actions')
<a href="{{ route('admin.departments.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouveau département
</a>
@endsection

@section('content')

@include('admin.partials._index-header', [
    'icon'  => 'fa-building',
    'title' => 'Départements',
    'desc'  => 'Structure départementale du Secrétariat Exécutif National',
    'color' => '#6366f1',
    'bg'    => '#ede9fe',
    'stats' => [
        ['label' => 'Total', 'value' => $departments->total()],
        ['label' => 'Agents', 'value' => $departments->sum('agents_count')],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Sections</th>
                    <th>Agents</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                <tr>
                    <td><span class="badge" style="background:#ede9fe;color:#6366f1;">{{ $dept->code }}</span></td>
                    <td class="fw-semibold">{{ $dept->nom }}</td>
                    <td><span class="badge badge-sen">{{ $dept->sections_count }}</span></td>
                    <td><span class="badge" style="background:#dbeafe;color:#2563eb;">{{ $dept->agents_count }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.departments.edit', $dept) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.departments.destroy', $dept) }}" method="POST"
                                  onsubmit="return confirm('Supprimer ce département ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-building"></i></div>
                            <p>Aucun département enregistré</p>
                            <a href="{{ route('admin.departments.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Ajouter
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($departments->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $departments->firstItem() }}–{{ $departments->lastItem() }} sur {{ $departments->total() }}</span>
        {{ $departments->links() }}
    </div>
    @endif
</div>
@endsection
