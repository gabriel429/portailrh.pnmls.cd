@extends('layouts.app')

@section('title', ($isRH ?? false) ? 'Gestion des demandes' : 'Mes Demandes')

@section('css')
<link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}">
<style>
    /* ── Status Tabs (same style as documents category tabs) ── */
    .req-tabs {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .req-tab {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem 1.1rem;
        border-radius: 25px;
        font-weight: 600;
        font-size: .88rem;
        border: 2px solid #e9ecef;
        background: #fff;
        color: #6c757d;
        cursor: pointer;
        transition: all .2s;
    }
    .req-tab:hover { border-color: #0077B5; color: #0077B5; }
    .req-tab.active {
        background: linear-gradient(135deg, #0077B5, #005a87);
        color: #fff;
        border-color: transparent;
    }
    .req-tab .tab-count {
        background: rgba(0,0,0,.08);
        padding: .1rem .5rem;
        border-radius: 12px;
        font-size: .75rem;
    }
    .req-tab.active .tab-count {
        background: rgba(255,255,255,.25);
    }

    /* ── Stats pills ── */
    .req-stats {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .req-stat {
        display: flex;
        align-items: center;
        gap: .6rem;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: .65rem 1.1rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
        flex: 1;
        min-width: 120px;
    }
    .req-stat-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
    }
    .req-stat-val { font-weight: 800; font-size: 1.2rem; color: #1a1a2e; }
    .req-stat-label { font-size: .75rem; color: #6c757d; }

    /* ── Loading fade ── */
    .req-loading {
        opacity: 0.4;
        pointer-events: none;
        transition: opacity .2s;
    }
</style>
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

        {{-- Stats pills --}}
        <div class="req-stats">
            <div class="req-stat">
                <div class="req-stat-icon" style="background:#e8f4fd;color:#0077B5;"><i class="fas fa-list"></i></div>
                <div>
                    <div class="req-stat-val" id="statTotal">{{ $counts['total'] ?? $requests->total() }}</div>
                    <div class="req-stat-label">Total</div>
                </div>
            </div>
            <div class="req-stat">
                <div class="req-stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="req-stat-val">{{ $counts['en_attente'] ?? 0 }}</div>
                    <div class="req-stat-label">En attente</div>
                </div>
            </div>
            <div class="req-stat">
                <div class="req-stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="req-stat-val">{{ $counts['approuve'] ?? 0 }}</div>
                    <div class="req-stat-label">Approuvées</div>
                </div>
            </div>
            <div class="req-stat">
                <div class="req-stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="req-stat-val">{{ $counts['rejete'] ?? 0 }}</div>
                    <div class="req-stat-label">Rejetées</div>
                </div>
            </div>
        </div>

        {{-- Status filter tabs --}}
        <div class="req-tabs" id="statusTabs">
            <button type="button" class="req-tab active" data-statut="">
                <i class="fas fa-th-large"></i> Toutes
                <span class="tab-count">{{ $counts['total'] ?? $requests->total() }}</span>
            </button>
            <button type="button" class="req-tab" data-statut="en_attente">
                <i class="fas fa-clock"></i> En attente
                <span class="tab-count">{{ $counts['en_attente'] ?? 0 }}</span>
            </button>
            <button type="button" class="req-tab" data-statut="approuvé">
                <i class="fas fa-check-circle"></i> Approuvées
                <span class="tab-count">{{ $counts['approuve'] ?? 0 }}</span>
            </button>
            <button type="button" class="req-tab" data-statut="rejeté">
                <i class="fas fa-times-circle"></i> Rejetées
                <span class="tab-count">{{ $counts['rejete'] ?? 0 }}</span>
            </button>
            <button type="button" class="req-tab" data-statut="annulé">
                <i class="fas fa-ban"></i> Annulées
                <span class="tab-count">{{ $counts['annule'] ?? 0 }}</span>
            </button>
        </div>

        {{-- Type filter tabs (for RH) --}}
        @if($isRH ?? false)
        <div class="req-tabs" id="typeTabs" style="margin-top:-0.5rem;">
            <button type="button" class="req-tab active" data-type="" style="font-size:.82rem;padding:.4rem .9rem;">
                <i class="fas fa-layer-group"></i> Tous types
            </button>
            <button type="button" class="req-tab" data-type="congé" style="font-size:.82rem;padding:.4rem .9rem;">
                <i class="fas fa-umbrella-beach"></i> Congé
            </button>
            <button type="button" class="req-tab" data-type="absence" style="font-size:.82rem;padding:.4rem .9rem;">
                <i class="fas fa-user-slash"></i> Absence
            </button>
            <button type="button" class="req-tab" data-type="permission" style="font-size:.82rem;padding:.4rem .9rem;">
                <i class="fas fa-hand-paper"></i> Permission
            </button>
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
document.addEventListener('DOMContentLoaded', function() {
    var dataContainer = document.getElementById('requestsData');
    if (!dataContainer) return;

    var baseUrl = '{{ route("requests.index") }}';
    var currentStatut = '';
    var currentType = '';

    function loadData(url) {
        dataContainer.style.opacity = '0.4';
        dataContainer.style.pointerEvents = 'none';

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'text/html');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                dataContainer.style.opacity = '1';
                dataContainer.style.pointerEvents = '';
                if (xhr.status === 200) {
                    dataContainer.innerHTML = xhr.responseText;
                    bindPagination();
                }
            }
        };
        xhr.send();
    }

    function buildUrl() {
        var params = [];
        if (currentStatut) params.push('statut=' + encodeURIComponent(currentStatut));
        if (currentType) params.push('type=' + encodeURIComponent(currentType));
        return baseUrl + (params.length ? '?' + params.join('&') : '');
    }

    // Bind status tab clicks
    document.querySelectorAll('#statusTabs .req-tab').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Update active style
            document.querySelectorAll('#statusTabs .req-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            this.classList.add('active');

            currentStatut = this.getAttribute('data-statut') || '';
            loadData(buildUrl());
        });
    });

    // Bind type tab clicks
    document.querySelectorAll('#typeTabs .req-tab').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            document.querySelectorAll('#typeTabs .req-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            this.classList.add('active');

            currentType = this.getAttribute('data-type') || '';
            loadData(buildUrl());
        });
    });

    // Intercept pagination clicks
    function bindPagination() {
        dataContainer.querySelectorAll('.pagination a').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadData(this.href);
            });
        });
    }

    bindPagination();
});
</script>
@endsection
