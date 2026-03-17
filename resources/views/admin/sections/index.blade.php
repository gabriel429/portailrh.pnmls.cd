@extends('admin.layouts.sidebar')
@section('title', 'Sections')
@section('page-title', 'Gestion des Sections')

@section('topbar-actions')
<a href="{{ route('admin.sections.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle section
</a>
@endsection

@section('content')
@php
    $nbSections = $sections->getCollection()->where('type', '!=', 'service_rattache')->count();
    $nbServices = $sections->getCollection()->where('type', 'service_rattache')->count();
@endphp

@include('admin.partials._index-header', [
    'icon'  => 'fa-sitemap',
    'title' => 'Sections & Services rattachés',
    'desc'  => 'Sections des départements et services rattachés au SEN/SENA',
    'color' => '#0891b2',
    'bg'    => '#e0f7fa',
    'stats' => [
        ['label' => 'Total', 'value' => $sections->total()],
        ['label' => 'Sections', 'value' => $nbSections],
        ['label' => 'Services', 'value' => $nbServices],
    ],
])

<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Département</th>
                    <th>Cellules</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sections as $section)
                <tr>
                    <td><span class="badge" style="background:#e0f7fa;color:#0891b2;">{{ $section->code }}</span></td>
                    <td class="fw-semibold">{{ $section->nom }}</td>
                    <td>
                        @if(($section->type ?? 'section') === 'service_rattache')
                            <span class="badge" style="background:#fef3c7;color:#d97706;"><i class="fas fa-project-diagram me-1"></i>Service rattaché</span>
                        @else
                            <span class="badge badge-sen"><i class="fas fa-layer-group me-1"></i>Section</span>
                        @endif
                    </td>
                    <td>
                        @if($section->department)
                            {{ $section->department->nom }}
                        @else
                            <span class="text-muted fst-italic">SEN/SENA</span>
                        @endif
                    </td>
                    <td><span class="badge" style="background:#f0f5ff;color:#4f46e5;">{{ $section->cellules_count }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.sections.edit', $section) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.sections.destroy', $section) }}" method="POST" onsubmit="return confirm('Supprimer cette section et ses cellules ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-sitemap"></i></div>
                        <p>Aucune section enregistrée</p>
                        <a href="{{ route('admin.sections.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Ajouter</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($sections->hasPages())
    <div class="pagination-wrapper">
        <span class="page-info">{{ $sections->firstItem() }}–{{ $sections->lastItem() }} sur {{ $sections->total() }}</span>
        {{ $sections->links() }}
    </div>
    @endif
</div>
@endsection
