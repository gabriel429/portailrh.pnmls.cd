@extends('layouts.app')

@section('title', ($isRH ?? false) ? 'Gestion des demandes' : 'Mes Demandes')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
@endsection

@section('content')
<div class="rh-modern">
    <div class="rh-list-shell">
        <section class="rh-hero">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    @if($isRH ?? false)
                        <h1 class="rh-title"><i class="fas fa-tasks me-2"></i>Gestion des demandes</h1>
                        <p class="rh-sub">Suivi, validation et historisation des demandes des agents.</p>
                    @else
                        <h1 class="rh-title"><i class="fas fa-paper-plane me-2"></i>Mes Demandes</h1>
                        <p class="rh-sub">Suivez l'état de vos demandes de congé, absence et permission.</p>
                    @endif
                </div>
                <div class="col-lg-4">
                    <div class="hero-tools">
                        <a href="{{ route('requests.create') }}" class="btn-rh main"><i class="fas fa-plus me-1"></i> Nouvelle demande</a>
                    </div>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($isRH ?? false)
        <div class="rh-toolbar">
            <div class="rh-filters">
                <form id="filterForm" method="GET" action="{{ route('requests.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="">Tous</option>
                            <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                            <option value="approuvé" {{ request('statut') === 'approuvé' ? 'selected' : '' }}>Approuvé</option>
                            <option value="rejeté" {{ request('statut') === 'rejeté' ? 'selected' : '' }}>Rejeté</option>
                            <option value="annulé" {{ request('statut') === 'annulé' ? 'selected' : '' }}>Annulé</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">Tous</option>
                            <option value="congé" {{ request('type') === 'congé' ? 'selected' : '' }}>Congé</option>
                            <option value="absence" {{ request('type') === 'absence' ? 'selected' : '' }}>Absence</option>
                            <option value="permission" {{ request('type') === 'permission' ? 'selected' : '' }}>Permission</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-filter me-2"></i> Filtrer
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-redo me-2"></i> Réinitialiser
                        </button>
                    </div>
                </form>
            </div>
            <div class="text-muted small d-none d-lg-block" id="totalCount">
                Total visible: {{ $requests->total() }}
            </div>
        </div>
        @endif

        <div class="rh-list-card p-3 p-lg-4" id="requestsData">
            @include('rh.requests.partials.table')
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function() {
    const dataContainer = document.getElementById('requestsData');
    const filterForm = document.getElementById('filterForm');
    const resetBtn = document.getElementById('resetFilters');
    const totalCount = document.getElementById('totalCount');

    function loadData(url) {
        dataContainer.style.opacity = '0.5';
        dataContainer.style.pointerEvents = 'none';

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            credentials: 'same-origin'
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
            dataContainer.innerHTML = html;
            dataContainer.style.opacity = '1';
            dataContainer.style.pointerEvents = '';
            bindPagination();
            // Update total count if present in response
            var match = html.match(/sur\s+(\d+)\s+demandes/);
            if (match && totalCount) {
                totalCount.textContent = 'Total visible: ' + match[1];
            }
        })
        .catch(function() {
            dataContainer.style.opacity = '1';
            dataContainer.style.pointerEvents = '';
        });
    }

    // AJAX filter form
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(filterForm);
            var params = new URLSearchParams(formData).toString();
            var url = filterForm.action + '?' + params;
            loadData(url);
            history.replaceState(null, '', url);
        });
    }

    // Reset filters
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (filterForm) {
                filterForm.querySelectorAll('select').forEach(function(s) { s.value = ''; });
            }
            loadData('{{ route("requests.index") }}');
            history.replaceState(null, '', '{{ route("requests.index") }}');
        });
    }

    // Intercept pagination clicks
    function bindPagination() {
        dataContainer.querySelectorAll('.pagination a').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadData(this.href);
                history.replaceState(null, '', this.href);
            });
        });
    }

    bindPagination();
})();
</script>
@endsection
