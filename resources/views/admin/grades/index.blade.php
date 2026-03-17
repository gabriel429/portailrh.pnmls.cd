@extends('admin.layouts.sidebar')
@section('title', 'Grades')
@section('page-title', 'Grades de la Fonction Publique')

@section('topbar-actions')
<a href="{{ route('admin.grades.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouveau grade
</a>
@endsection

@section('content')
@php
    $totalGrades = $grades->flatten()->count();
    $catMeta = [
        'A' => ['label' => 'Haut cadre de commandement',  'color' => '#3b5de7', 'bg' => '#eef1fc', 'icon' => 'fa-star'],
        'B' => ['label' => 'Agent de collaboration',       'color' => '#0891b2', 'bg' => '#e0f7fa', 'icon' => 'fa-handshake'],
        'C' => ['label' => "Agent d'exécution",            'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-cog'],
    ];
@endphp

@include('admin.partials._index-header', [
    'icon'  => 'fa-layer-group',
    'title' => 'Grades',
    'desc'  => 'Hiérarchie des grades de la Fonction Publique',
    'color' => '#10b981',
    'bg'    => '#d1fae5',
    'stats' => [
        ['label' => 'Total', 'value' => $totalGrades],
        ['label' => 'Cat. A', 'value' => isset($grades['A']) ? $grades['A']->count() : 0],
        ['label' => 'Cat. B', 'value' => isset($grades['B']) ? $grades['B']->count() : 0],
        ['label' => 'Cat. C', 'value' => isset($grades['C']) ? $grades['C']->count() : 0],
    ],
])

@foreach($catMeta as $cat => $meta)
@if(isset($grades[$cat]) && $grades[$cat]->count() > 0)
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div style="width:32px;height:32px;border-radius:8px;background:{{ $meta['bg'] }};color:{{ $meta['color'] }};display:flex;align-items:center;justify-content:center;font-size:.8rem;">
            <i class="fas {{ $meta['icon'] }}"></i>
        </div>
        <div>
            <span class="fw-bold" style="font-size:.9rem;">Catégorie {{ $cat }}</span>
            <span class="text-muted" style="font-size:.78rem;">— {{ $meta['label'] }}</span>
        </div>
        <span class="badge" style="background:{{ $meta['bg'] }};color:{{ $meta['color'] }};margin-left:auto;">{{ $grades[$cat]->count() }} grades</span>
    </div>
    <div class="admin-table">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:70px">Ordre</th>
                    <th>Intitulé du grade</th>
                    <th style="width:110px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($grades[$cat] as $grade)
                <tr>
                    <td><span class="badge" style="background:{{ $meta['bg'] }};color:{{ $meta['color'] }};">{{ $grade->ordre }}</span></td>
                    <td class="fw-semibold">{{ $grade->libelle }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.grades.edit', $grade) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.grades.destroy', $grade) }}" method="POST" onsubmit="return confirm('Supprimer ce grade ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endforeach

@if($grades->isEmpty())
<div class="admin-table">
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-layer-group"></i></div>
        <p>Aucun grade enregistré</p>
        <a href="{{ route('admin.grades.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Ajouter</a>
    </div>
</div>
@endif
@endsection
