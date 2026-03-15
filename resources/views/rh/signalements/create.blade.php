@extends('layouts.app')

@section('title', 'Nouveau Signalement - Portail RH PNMLS')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
@php /** @var \Illuminate\Support\Collection $agents */ @endphp
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <h1 class="rh-title"><i class="fas fa-plus-circle me-2"></i>Nouveau signalement</h1>
                    <p class="rh-sub">Declarer un incident et fixer sa severite initiale.</p>
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('signalements.index') }}" class="btn-rh alt"><i class="fas fa-arrow-left me-1"></i> Retour liste</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="rh-list-card p-3 p-lg-4">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Erreurs de validation</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('signalements.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label for="agent_id" class="form-label">Agent</label>
                    <select class="form-select @error('agent_id') is-invalid @enderror" id="agent_id" name="agent_id" required>
                        <option value="">Selectionner un agent</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected(old('agent_id') == $agent->id)>
                                ({{ $agent->id_agent }}) {{ $agent->prenom }} {{ $agent->nom }}
                            </option>
                        @endforeach
                    </select>
                    @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="type" class="form-label">Type</label>
                    <input type="text" class="form-control @error('type') is-invalid @enderror" id="type" name="type" value="{{ old('type') }}" required>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="severite" class="form-label">Severite</label>
                    <select class="form-select @error('severite') is-invalid @enderror" id="severite" name="severite" required>
                        <option value="">Selectionner</option>
                        <option value="basse" @selected(old('severite') === 'basse')>Basse</option>
                        <option value="moyenne" @selected(old('severite') === 'moyenne')>Moyenne</option>
                        <option value="haute" @selected(old('severite') === 'haute')>Haute</option>
                    </select>
                    @error('severite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="observations" class="form-label">Observations</label>
                    <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="3">{{ old('observations') }}</textarea>
                    @error('observations')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Creer le signalement</button>
                    <a href="{{ route('signalements.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
