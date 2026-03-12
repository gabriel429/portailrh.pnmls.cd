@extends('admin.layouts.sidebar')
@section('title', 'Grades')
@section('page-title', 'Grades de la Fonction Publique')

@section('topbar-actions')
<a href="{{ route('admin.grades.create') }}" class="btn btn-success btn-sm">
    <i class="fas fa-plus me-1"></i> Nouveau grade
</a>
@endsection

@section('content')
@foreach(['A' => ['label' => 'Haut cadre', 'color' => 'primary'], 'B' => ['label' => 'Agent de collaboration', 'color' => 'info'], 'C' => ["label" => "Agents d'exécution", 'color' => 'secondary']] as $cat => $meta)
@if(isset($grades[$cat]))
<div class="mb-4">
    <h6 class="fw-bold mb-2">
        <span class="badge bg-{{ $meta['color'] }} me-2">{{ $cat }}</span> {{ $meta['label'] }}
    </h6>
    <div class="admin-table">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:60px">Ordre</th>
                    <th>Intitulé du grade</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($grades[$cat] as $grade)
                <tr>
                    <td><span class="badge bg-{{ $meta['color'] }}">{{ $grade->ordre }}</span></td>
                    <td>{{ $grade->libelle }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.grades.edit', $grade) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.grades.destroy', $grade) }}" method="POST"
                                  onsubmit="return confirm('Supprimer ce grade ?')">
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
<div class="text-center text-muted py-5">Aucun grade enregistré.</div>
@endif
@endsection
