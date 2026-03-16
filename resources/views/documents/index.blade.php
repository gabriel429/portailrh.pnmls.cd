@extends('layouts.app')

@section('title', 'Documents - GED')

@section('css')
<style>
    /* ── Hero Header ── */
    .docs-hero {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 50%, #155d27 100%);
        border-radius: 16px;
        padding: 2.5rem 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        color: #fff;
    }
    .docs-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .docs-hero .hero-content { position: relative; z-index: 2; }
    .docs-hero h2 { font-weight: 800; font-size: 1.8rem; margin-bottom: .25rem; }
    .docs-hero p { opacity: .85; margin-bottom: 0; }
    .btn-upload {
        background: rgba(255,255,255,.2);
        border: 2px solid rgba(255,255,255,.4);
        color: #fff;
        font-weight: 700;
        padding: .6rem 1.5rem;
        border-radius: 12px;
        transition: all .2s;
        backdrop-filter: blur(4px);
    }
    .btn-upload:hover {
        background: #fff;
        color: #28a745;
        border-color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,.15);
    }

    /* ── Filter Card ── */
    .filter-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        border: 1px solid #e9ecef;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .filter-card .form-control,
    .filter-card .form-select {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: .6rem 1rem;
        transition: border-color .2s;
    }
    .filter-card .form-control:focus,
    .filter-card .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 .2rem rgba(40,167,69,.15);
    }
    .btn-filter {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        color: #fff;
        font-weight: 600;
        border-radius: 10px;
        padding: .6rem 1.5rem;
        transition: all .2s;
    }
    .btn-filter:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40,167,69,.3);
        color: #fff;
    }
    .btn-reset {
        background: #f5f5f5;
        border: 2px solid #e9ecef;
        color: #666;
        border-radius: 10px;
        padding: .6rem .85rem;
        transition: all .2s;
    }
    .btn-reset:hover {
        background: #e9ecef;
        color: #333;
    }

    /* ── Category Tabs ── */
    .cat-tabs {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .cat-tab {
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
    .cat-tab:hover { border-color: #28a745; color: #28a745; }
    .cat-tab.active, .cat-tab[aria-selected="true"] {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: #fff;
        border-color: transparent;
    }
    .cat-tab .cat-count {
        background: rgba(0,0,0,.08);
        padding: .1rem .5rem;
        border-radius: 12px;
        font-size: .75rem;
    }
    .cat-tab.active .cat-count,
    .cat-tab[aria-selected="true"] .cat-count {
        background: rgba(255,255,255,.25);
    }

    /* ── Document Card ── */
    .doc-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,.05);
        border: 1px solid #e9ecef;
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .doc-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,.1);
    }
    .doc-card-top {
        padding: 1.5rem 1.25rem 1rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        flex: 1;
    }
    .doc-icon-wrap {
        width: 52px; height: 52px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 1.4rem;
    }
    .doc-icon-wrap.pdf { background: #fdeaec; color: #dc3545; }
    .doc-icon-wrap.image { background: #e0f4ff; color: #0dcaf0; }
    .doc-icon-wrap.word { background: #e3effe; color: #0d6efd; }
    .doc-icon-wrap.excel { background: #e6f7ef; color: #28a745; }
    .doc-icon-wrap.other { background: #f5f5f5; color: #6c757d; }
    .doc-card-info h6 {
        font-weight: 700;
        color: #1a1a2e;
        font-size: .95rem;
        margin-bottom: .3rem;
        line-height: 1.3;
    }
    .doc-card-desc {
        color: #6c757d;
        font-size: .82rem;
        line-height: 1.4;
        margin-bottom: .4rem;
    }
    .doc-meta {
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: .78rem;
        color: #999;
    }
    .doc-meta span { display: flex; align-items: center; gap: .25rem; }
    .doc-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .6rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: .75rem;
    }
    .doc-badge.identite { background: #e8f4fd; color: #0077B5; }
    .doc-badge.parcours { background: #fff3e0; color: #e67e22; }
    .doc-badge.carriere { background: #ede9fe; color: #7c3aed; }
    .doc-badge.mission { background: #e6f7ef; color: #28a745; }

    /* ── Card Footer Actions ── */
    .doc-card-actions {
        display: flex;
        border-top: 1px solid #f0f2f5;
    }
    .doc-card-actions .doc-action {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .7rem .5rem;
        font-size: .82rem;
        font-weight: 600;
        color: #6c757d;
        text-decoration: none;
        border: none;
        background: none;
        transition: all .15s;
        cursor: pointer;
    }
    .doc-card-actions .doc-action + .doc-action { border-left: 1px solid #f0f2f5; }
    .doc-card-actions .doc-action:hover { background: #f8f9fc; }
    .doc-card-actions .doc-action.view:hover { color: #0077B5; }
    .doc-card-actions .doc-action.download:hover { color: #28a745; }
    .doc-card-actions .doc-action.delete:hover { color: #dc3545; background: #fef5f5; }

    /* ── Empty State ── */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: #fff;
        border-radius: 14px;
        border: 2px dashed #dee2e6;
    }
    .empty-state-icon {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e6f7ef, #c8f0d8);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2rem; color: #28a745;
    }
    .empty-state h5 { font-weight: 700; color: #1a1a2e; }
    .empty-state p { color: #6c757d; max-width: 400px; margin: 0 auto 1.25rem; }

    /* ── Pagination ── */
    .pagination-wrap {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
        padding: 1rem 1.5rem;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }
    .pagination-wrap .page-info { color: #6c757d; font-size: .88rem; }
    .pagination-wrap .pagination { margin-bottom: 0; }
    .pagination-wrap .page-link {
        border-radius: 8px !important;
        margin: 0 2px;
        border: none;
        color: #333;
        font-weight: 600;
    }
    .pagination-wrap .page-item.active .page-link {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border-color: transparent;
    }

    /* ── Stat Summary ── */
    .stats-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .stat-pill {
        display: flex;
        align-items: center;
        gap: .6rem;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: .65rem 1.1rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
        flex: 1;
        min-width: 140px;
    }
    .stat-pill-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
    }
    .stat-pill-val { font-weight: 800; font-size: 1.2rem; color: #1a1a2e; }
    .stat-pill-label { font-size: .75rem; color: #6c757d; }
</style>
@endsection

@section('content')
<div class="container py-4">

    {{-- ═══ Hero Header ═══ --}}
    <div class="docs-hero">
        <div class="hero-content d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="fas fa-folder-open me-2"></i>Gestion Électronique de Documents</h2>
                <p>Organisez, consultez et gérez vos documents en toute simplicité</p>
            </div>
            <a href="{{ route('documents.create') }}" class="btn btn-upload">
                <i class="fas fa-cloud-upload-alt me-2"></i>Uploader un document
            </a>
        </div>
    </div>

    {{-- ═══ Stats Summary ═══ --}}
    @php
        $countIdentite = $documents->getCollection()->where('categorie', 'identite')->count();
        $countParcours = $documents->getCollection()->where('categorie', 'parcours')->count();
        $countCarriere = $documents->getCollection()->where('categorie', 'carriere')->count();
        $countMission  = $documents->getCollection()->where('categorie', 'mission')->count();
    @endphp
    <div class="stats-row">
        <div class="stat-pill">
            <div class="stat-pill-icon" style="background:#e6f7ef;color:#28a745;">
                <i class="fas fa-file-alt"></i>
            </div>
            <div>
                <div class="stat-pill-val">{{ $documents->total() }}</div>
                <div class="stat-pill-label">Total documents</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="stat-pill-icon" style="background:#e8f4fd;color:#0077B5;">
                <i class="fas fa-id-card"></i>
            </div>
            <div>
                <div class="stat-pill-val">{{ $countIdentite }}</div>
                <div class="stat-pill-label">Identité</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="stat-pill-icon" style="background:#fff3e0;color:#e67e22;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
                <div class="stat-pill-val">{{ $countParcours }}</div>
                <div class="stat-pill-label">Parcours</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="stat-pill-icon" style="background:#ede9fe;color:#7c3aed;">
                <i class="fas fa-briefcase"></i>
            </div>
            <div>
                <div class="stat-pill-val">{{ $countCarriere }}</div>
                <div class="stat-pill-label">Carrière</div>
            </div>
        </div>
    </div>

    {{-- ═══ Filtres ═══ --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('documents.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold"><i class="fas fa-filter me-1 text-success"></i>Catégorie</label>
                <select name="categorie" class="form-select">
                    <option value="">Toutes les catégories</option>
                    <option value="identite" {{ request('categorie') === 'identite' ? 'selected' : '' }}>Identité</option>
                    <option value="parcours" {{ request('categorie') === 'parcours' ? 'selected' : '' }}>Parcours</option>
                    <option value="carriere" {{ request('categorie') === 'carriere' ? 'selected' : '' }}>Carrière</option>
                    <option value="mission" {{ request('categorie') === 'mission' ? 'selected' : '' }}>Mission</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold"><i class="fas fa-search me-1 text-success"></i>Recherche</label>
                <input type="text" name="search" class="form-control" placeholder="Rechercher un document..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-filter flex-grow-1">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('documents.index') }}" class="btn btn-reset" title="Réinitialiser">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- ═══ Category Tabs ═══ --}}
    <div class="cat-tabs" role="tablist">
        <button class="cat-tab active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-selected="true">
            <i class="fas fa-th-large"></i> Tous
            <span class="cat-count">{{ $documents->total() }}</span>
        </button>
        <button class="cat-tab" id="identite-tab" data-bs-toggle="tab" data-bs-target="#identite" type="button" role="tab" aria-selected="false">
            <i class="fas fa-id-card"></i> Identité
        </button>
        <button class="cat-tab" id="parcours-tab" data-bs-toggle="tab" data-bs-target="#parcours" type="button" role="tab" aria-selected="false">
            <i class="fas fa-graduation-cap"></i> Parcours
        </button>
        <button class="cat-tab" id="carriere-tab" data-bs-toggle="tab" data-bs-target="#carriere" type="button" role="tab" aria-selected="false">
            <i class="fas fa-briefcase"></i> Carrière
        </button>
        <button class="cat-tab" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission" type="button" role="tab" aria-selected="false">
            <i class="fas fa-plane"></i> Mission
        </button>
    </div>

    {{-- ═══ Documents Grid ═══ --}}
    <div class="tab-content">
        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
            @include('documents.partials.list', ['docs' => $documents])
        </div>
    </div>

    {{-- ═══ Pagination ═══ --}}
    @if($documents->count() > 0)
        <div class="pagination-wrap">
            <div class="page-info">
                Affichage <strong>{{ $documents->firstItem() }}</strong> à <strong>{{ $documents->lastItem() }}</strong>
                sur <strong>{{ $documents->total() }}</strong> documents
            </div>
            {{ $documents->links() }}
        </div>
    @endif
</div>
@endsection
