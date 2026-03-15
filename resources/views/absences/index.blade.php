@extends('layouts.app')

@section('title', 'Mes Absences')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-shell">
        <section class="rh-hero">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-calendar-times me-2"></i>Mes Absences</h1>
                    <p class="rh-sub">Liste des jours ou votre presence n'a pas ete enregistree.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('dashboard') }}" class="btn-rh alt">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Filtres --}}
        <div class="dash-panel mt-3">
            <div class="p-3">
                <form method="GET" action="{{ route('mes-absences') }}" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label mb-1 small fw-bold">Annee</label>
                        <select name="annee" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($y = now()->year; $y >= 2023; $y--)
                                <option value="{{ $y }}" {{ $annee == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-1 small fw-bold">Mois</label>
                        <select name="mois" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Tous</option>
                            @php
                                $moisLabels = [1 => 'Janvier', 2 => 'Fevrier', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Aout', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Decembre'];
                            @endphp
                            @foreach($moisLabels as $num => $label)
                                <option value="{{ $num }}" {{ request('mois') == $num ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('mes-absences') }}" class="btn btn-sm btn-outline-secondary">Reinitialiser</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Resume --}}
        <div class="row g-3 mt-2">
            <div class="col-6 col-md-4">
                <div class="p-3 rounded text-center" style="background: #fce4ec;">
                    <h3 class="mb-0 fw-bold text-danger">{{ $totalAbsences }}</h3>
                    <small class="text-muted">Absences en {{ $annee }}</small>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="p-3 rounded text-center" style="background: #f0f4f8;">
                    <h3 class="mb-0 fw-bold">{{ $agent->nom_complet ?? 'N/A' }}</h3>
                    <small class="text-muted">Agent</small>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-3 rounded text-center" style="background: #fff3e0;">
                    <h3 class="mb-0 fw-bold text-warning">
                        @if($totalAbsences > 10)
                            <i class="fas fa-exclamation-triangle"></i>
                        @elseif($totalAbsences > 5)
                            <i class="fas fa-exclamation-circle"></i>
                        @else
                            <i class="fas fa-check-circle text-success"></i>
                        @endif
                    </h3>
                    <small class="text-muted">
                        @if($totalAbsences > 10)
                            Taux eleve
                        @elseif($totalAbsences > 5)
                            A surveiller
                        @else
                            Normal
                        @endif
                    </small>
                </div>
            </div>
        </div>

        {{-- Liste des absences --}}
        <div class="dash-panel mt-3">
            <header class="panel-head">
                <div>
                    <h3 class="panel-title">
                        <i class="fas fa-list me-2 text-danger"></i>Jours d'absence
                        <span class="badge bg-danger ms-2" style="font-size: 0.7rem;">{{ $totalAbsences }}</span>
                    </h3>
                </div>
            </header>

            @if($absences instanceof \Illuminate\Pagination\LengthAwarePaginator && $absences->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th style="width: 50px;">#</th>
                                <th>Date</th>
                                <th>Jour</th>
                                <th>Observations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($absences as $index => $pointage)
                                <tr>
                                    <td class="text-muted">{{ $absences->firstItem() + $index }}</td>
                                    <td>
                                        <i class="fas fa-calendar-day text-danger me-1"></i>
                                        <strong>{{ $pointage->date_pointage->format('d/m/Y') }}</strong>
                                    </td>
                                    <td>
                                        @php
                                            $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                                            $jourSemaine = $jours[$pointage->date_pointage->dayOfWeek];
                                        @endphp
                                        {{ $jourSemaine }}
                                    </td>
                                    <td>
                                        @if($pointage->observations)
                                            <span class="text-muted">{{ $pointage->observations }}</span>
                                        @else
                                            <span class="text-muted fst-italic">Aucune observation</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($absences->hasPages())
                    <div class="p-3">
                        {{ $absences->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3 d-block text-success"></i>
                    <p class="mb-0">Aucune absence detectee pour cette periode.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
