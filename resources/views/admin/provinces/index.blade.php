@extends('admin.layouts.sidebar')
@section('title', 'Provinces')
@section('page-title', 'Gestion des Provinces')

@section('topbar-actions')
<a href="{{ route('admin.provinces.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Nouvelle province
</a>
@endsection

@section('content')
<div class="admin-table">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Ville secrétariat</th>
                    <th>Gouverneur</th>
                    <th>Mail officiel</th>
                    <th>Agents</th>
                    <th>Depts</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($provinces as $province)
                <tr>
                    <td><span class="badge bg-primary">{{ $province->code }}</span></td>
                    <td class="fw-semibold">{{ $province->nom }}</td>
                    <td>{{ $province->ville_secretariat ?? '–' }}</td>
                    <td>{{ $province->nom_gouverneur ?? '–' }}</td>
                    <td>{{ $province->email_officiel ?? '–' }}</td>
                    <td><span class="badge bg-secondary">{{ $province->agents_count }}</span></td>
                    <td><span class="badge bg-info text-dark">{{ $province->departments_count }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.provinces.edit', $province) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.provinces.destroy', $province) }}" method="POST"
                                  onsubmit="return confirm('Supprimer cette province ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Aucune province enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($provinces->hasPages())
    <div class="p-3">{{ $provinces->links() }}</div>
    @endif
</div>
@endsection
