@extends('admin.layouts.sidebar')
@section('title', 'Organes')
@section('page-title', 'Gestion des Organes')

@section('topbar-actions')
<a href="{{ route('admin.organes.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Ajouter un organe
</a>
@endsection

@section('content')

@include('admin.partials._index-header', [
    'icon'  => 'fa-landmark',
    'title' => 'Organes',
    'desc'  => 'Secrétariats Exécutifs du programme (SEN, SEP, SEL)',
    'color' => '#3b5de7',
    'bg'    => '#eef1fc',
    'stats' => [
        ['label' => 'Total', 'value' => $organes->total()],
        ['label' => 'Actifs', 'value' => $organes->getCollection()->where('actif', true)->count()],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Sigle</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($organes as $o)
                <tr>
                    <td><span class="badge badge-sen">{{ $o->code }}</span></td>
                    <td class="fw-semibold">{{ $o->nom }}</td>
                    <td><span class="badge" style="background:#f1f5f9;color:#64748b;">{{ $o->sigle ?? '–' }}</span></td>
                    <td>
                        @if($o->actif)
                            <span class="badge" style="background:#d1fae5;color:#10b981;"><i class="fas fa-check-circle me-1"></i>Actif</span>
                        @else
                            <span class="badge" style="background:#fee2e2;color:#ef4444;"><i class="fas fa-times-circle me-1"></i>Inactif</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.organes.edit', $o) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.organes.destroy', $o) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet organe ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-landmark"></i></div>
                        <p>Aucun organe enregistré</p>
                        <a href="{{ route('admin.organes.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Ajouter</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($organes->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $organes->firstItem() }}–{{ $organes->lastItem() }} sur {{ $organes->total() }}</span>
        {{ $organes->links() }}
    </div>
    @endif
</div>
@endsection
